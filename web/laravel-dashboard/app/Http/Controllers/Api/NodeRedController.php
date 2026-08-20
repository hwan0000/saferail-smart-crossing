<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiPrediction;
use App\Models\DeviceStatus;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeRedController extends Controller
{
    // ----------------------------------------------------------------
    // SYNC SEMUA DATA (Meringankan beban server)
    // ----------------------------------------------------------------
    public function storeSync(Request $request): JsonResponse
    {
        // Simpan payload mentah ke log. WAJIB dicek di storage/logs/laravel.log
        // kalau data masih terasa tidak sesuai -- ini cara paling pasti untuk
        // tahu persis field apa & bentuk apa yang benar-benar dikirim Node-RED.
        \Log::info('NodeRed /sync payload diterima', $request->all());

        // --- Ambil jarak sensor ---
        // Prioritas ke field FLAT (persis seperti yang dicetak sendJSON() di Arduino:
        // distance_approach_cm, distance_exit_cm). Fallback ke bentuk nested
        // (sensor.distance_cm) untuk jaga-jaga kalau Node-RED diubah nanti.
        $dist = $request->input(
            'distance_approach_cm',
            $request->input('distance_cm', $request->input('sensor.distance_cm', 999))
        );
        $distExit = $request->input(
            'distance_exit_cm',
            $request->input('sensor.distance_exit_cm', 999)
        );

        // Paksa jadi float yang valid; kalau field tidak ada / bukan angka -> 999 (aman, tidak trigger apa pun)
        $dist     = is_numeric($dist) ? (float) $dist : 999.0;
        $distExit = is_numeric($distExit) ? (float) $distExit : 999.0;

        // --- Percaya ke state yang dikirim Arduino, JANGAN dihitung ulang di sini ---
        // Arduino sudah punya debounce (butuh beberapa bacaan berturut-turut sebelum
        // dianggap valid), fail-safe (timeout sensor dianggap bahaya), dan sekarang
        // dukungan 2 arah (sisi mana pun bisa jadi entry/exit tergantung datangnya
        // kereta dari mana). Threshold jarak (50cm) & logic if/elseif yang lama di
        // sini tidak tahu apa-apa soal itu semua -- dia recompute pakai angka jarak
        // mentah dan selalu anggap sensor pertama = "approach", jadi hasilnya bisa
        // beda dari kondisi gate fisik yang sebenarnya, apalagi untuk kereta dari
        // arah sebaliknya. Server di sini cuma mencatat apa yang dilaporkan device.
        $status         = $request->input('status', $request->input('sensor.status', 'normal'));
        $crossingStatus = $request->input('crossing_status', $request->input('ai.crossing_status', 'safe'));
        $barrierState   = $request->input('gate_state', $request->input('barrier.state', 'open'));
        $signalState    = $request->input('signal_state', $request->input('signal.state', 'green'));
        $eventType      = $request->input('event_type', $request->input('sensor.event_type', 'reading'));

        // 'A' / 'B' -- which sonar the current crossing entered from ('none' when SAFE).
        $entrySide = $request->input('entry_side', $request->input('sensor.entry_side', 'none'));
        $entrySide = in_array($entrySide, ['A', 'B'], true) ? $entrySide : null;

        // 1. Simpan Sensor
        $sensorReading = SensorReading::create([
            'distance_cm'      => $dist,
            'distance_exit_cm' => $distExit,
            'entry_side'       => $entrySide,
            'speed_cms'        => $request->input('speed_cms', $request->input('sensor.speed_cms', 0)),
            'event_type'       => $eventType,
            'status'           => $status,
            'recorded_at'      => now(),
        ]);

        // 2. Simpan Barrier
        DeviceStatus::create([
            'device_name'      => 'barrier_gate',
            'state'            => $barrierState,
            'actuator_voltage' => 12.4,
            'mode'             => 'auto',
            'changed_at'       => now(),
        ]);

        // 3. Simpan Signal
        DeviceStatus::create([
            'device_name' => 'traffic_signal',
            'state'       => $signalState,
            'mode'        => 'auto',
            'changed_at'  => now(),
        ]);

        // 4. Simpan AI Prediction
        $aiPrediction = AiPrediction::create([
            'is_train_detected'  => ($crossingStatus !== 'safe'),
            'crossing_status'    => $crossingStatus,
            'confidence'         => $request->input('confidence', $request->input('ai.confidence', 98.0)),
            'distance_km'        => $request->input('distance_km', $request->input('ai.distance_km', 0)),
            'approach_speed_kmh' => $request->input('approach_speed_kmh', $request->input('ai.approach_speed_kmh', 0)),
            'predicted_at'       => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'All data synced successfully in one request.',
            'data'    => [
                'status'             => $status,
                'crossing_status'    => $crossingStatus,
                'barrier_state'      => $barrierState,
                'signal_state'       => $signalState,
                'sensor_reading_id'  => $sensorReading->id,
                'ai_prediction_id'   => $aiPrediction->id,
            ],
        ], 201);
    }

    // ----------------------------------------------------------------
    // SENSOR READINGS
    // ----------------------------------------------------------------

    /**
     * Terima data dari sensor HC-SR04 (Node-RED mengirim POST ke /api/sensor).
     *
     * Contoh payload JSON dari Node-RED:
     * {
     *   "distance_cm": 145.5,
     *   "speed_cms": 18.2,
     *   "event_type": "approach_detected",
     *   "status": "normal"
     * }
     */
    public function storeSensor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'distance_cm' => 'required|numeric|min:0',
            'speed_cms'   => 'nullable|numeric|min:0',
            'event_type'  => 'nullable|string|in:reading,approach_detected,departure_complete,proximity_alert',
            'status'      => 'nullable|string|in:normal,warning,danger',
        ]);

        // Tentukan status otomatis berdasarkan jarak jika tidak dikirim
        if (empty($validated['status'])) {
            $validated['status'] = match(true) {
                $validated['distance_cm'] < 50  => 'danger',
                $validated['distance_cm'] < 150 => 'warning',
                default                         => 'normal',
            };
        }

        $reading = SensorReading::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sensor reading saved.',
            'data'    => $reading,
        ], 201);
    }

    /**
     * Ambil data sensor terbaru (untuk ditampilkan di dashboard).
     */
    public function latestSensor(): JsonResponse
    {
        $latest = SensorReading::latest('recorded_at')->first();
        $recent = SensorReading::latest('recorded_at')->limit(20)->get();

        return response()->json([
            'success' => true,
            'latest'  => $latest,
            'recent'  => $recent,
        ]);
    }

    // ----------------------------------------------------------------
    // DEVICE STATUSES
    // ----------------------------------------------------------------

    /**
     * Terima status perangkat (palang/lampu) dari Node-RED.
     *
     * Contoh payload JSON dari Node-RED (palang):
     * {
     *   "device_name": "barrier_gate",
     *   "state": "closed",
     *   "actuator_voltage": 12.4,
     *   "mode": "auto"
     * }
     *
     * Contoh payload JSON dari Node-RED (lampu):
     * {
     *   "device_name": "traffic_signal",
     *   "state": "red",
     *   "mode": "auto"
     * }
     */
    public function storeDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_name'       => 'required|string|in:barrier_gate,traffic_signal',
            'state'             => 'required|string',
            'actuator_voltage'  => 'nullable|numeric',
            'mode'              => 'nullable|string|in:auto,manual',
        ]);

        $status = DeviceStatus::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Device status saved.',
            'data'    => $status,
        ], 201);
    }

    /**
     * Ambil status perangkat terbaru (untuk ditampilkan di dashboard).
     */
    public function latestDevices(): JsonResponse
    {
        $barrier = DeviceStatus::where('device_name', 'barrier_gate')
            ->latest('changed_at')->first();

        $signal = DeviceStatus::where('device_name', 'traffic_signal')
            ->latest('changed_at')->first();

        return response()->json([
            'success'       => true,
            'barrier_gate'  => $barrier,
            'traffic_signal'=> $signal,
        ]);
    }

    // ----------------------------------------------------------------
    // AI PREDICTIONS
    // ----------------------------------------------------------------

    /**
     * Terima hasil prediksi AI dari Node-RED.
     *
     * Contoh payload JSON dari Node-RED:
     * {
     *   "is_train_detected": true,
     *   "crossing_status": "warning",
     *   "confidence": 92.5,
     *   "distance_km": 0.8,
     *   "approach_speed_kmh": 60
     * }
     */
    public function storePrediction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_train_detected'  => 'required|boolean',
            'crossing_status'    => 'required|string|in:safe,warning,danger',
            'confidence'         => 'required|numeric|min:0|max:100',
            'distance_km'        => 'nullable|numeric|min:0',
            'approach_speed_kmh' => 'nullable|numeric|min:0',
        ]);

        $prediction = AiPrediction::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'AI prediction saved.',
            'data'    => $prediction,
        ], 201);
    }

    /**
     * Ambil prediksi AI terbaru (untuk ditampilkan di halaman utama dashboard).
     */
    public function latestPrediction(): JsonResponse
    {
        $latest = AiPrediction::latest('predicted_at')->first();

        return response()->json([
            'success' => true,
            'data'    => $latest,
        ]);
    }

    /**
     * Endpoint terpadu untuk real-time polling frontend.
     */
    public function latestDashboard(): JsonResponse
    {
        $latestPrediction = AiPrediction::latest('id')->first();
        $latestSensor     = SensorReading::latest('id')->first();
        $barrierStatus    = DeviceStatus::where('device_name', 'barrier_gate')->latest('id')->first();
        $signalStatus     = DeviceStatus::where('device_name', 'traffic_signal')->latest('id')->first();

        return response()->json([
            'success'          => true,
            'latestPrediction' => $latestPrediction,
            'latestSensor'     => $latestSensor,
            'barrierStatus'    => $barrierStatus,
            'signalStatus'     => $signalStatus,
            'server_time'      => now()->toIso8601String(),
        ]);
    }
}