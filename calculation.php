
class PneumaticCalculator {

    // --- CONSTANTES ---
    const PI = 3.141592653589793;
    const GC = 32.174; // lb·ft/lbf·s²
    const G = 32.2;   // ft/s²
    const P_NORM = 1.01; // bar
    const T_NORM = 0;    // °C
    const MOLAR_VOLUME = 22.414; // L/mol o m³/kmol
    const REYNOLDS_CONSTANT = 0.0002; // Constante para cálculo de Re

    // --- CONVERSORES DE UNIDADES ---
    const IN_TO_MM = 25.4;
    const IN_TO_FT = 1.0 / 12.0;
    const M_TO_FT = 3.280839895;
    const KG_M3_TO_LB_FT3 = 0.0624279606;
    const TPH_TO_KGH = 1000.0;
    const KG_TO_LB = 2.20462262185;
    const MBAR_TO_PSI = 0.0145037738;
    const MBAR_TO_BAR = 0.001;
    const BAR_TO_PSI = 14.5037738;
    const MPS_TO_FTS = 3.280839895;
    const CFM_FROM_M3H = 0.588577779;
    
    // private $pdo; // Eliminado

    public function __construct() { // $pdo eliminado
        // $this->pdo = $pdo; // Eliminado
    }

    // --- FUNCIONES DE UTILIDAD ---
    private static function c_to_f($c) { return $c * 9 / 5 + 32; }
    private static function k_from_c($c) { return $c + 273.15; }
    private static function diam_in_to_mm($val) { return $val * self::IN_TO_MM; }
    private static function diam_in_to_ft($val) { return $val * self::IN_TO_FT; }
    private static function area_m2_from_dmm($d_mm) { return self::PI * pow($d_mm / 1000, 2) / 4; }
    private static function area_ft2_from_dft($d_ft) { return self::PI * pow($d_ft, 2) / 4; }

    // --- MÓDULOS DE CÁLCULO ---

    public function calculateAtmospheric($atm, $gas) {
        $res = [];
        $res['Tamb_C'] = (float)$atm['Tamb_C'];
        $res['Tamb_F'] = self::c_to_f($res['Tamb_C']);
        $res['Patm_mbar'] = 1013 * pow(1 - (float)$atm['Height_m'] / 44300, 5.25);
        $res['Patm_PSI'] = $res['Patm_mbar'] * self::MBAR_TO_PSI;
        $res['Patm_bar'] = $res['Patm_mbar'] * self::MBAR_TO_BAR;

        $ro_air_norm = $gas['PM'] / self::MOLAR_VOLUME; // kg/Nm³

        $ro_gas_metric = $ro_air_norm / (self::P_NORM / (273.15 + self::T_NORM) * (273.15 + $res['Tamb_C']) / $res['Patm_bar']);
        $res['ro_gas_us'] = $ro_gas_metric * self::KG_M3_TO_LB_FT3;
        return $res;
    }

    public function calculateGas($gas) {
        $res = [];
        $moisture_air = (float)$gas['Moisture_air'];
        $x_O2 = (0.21 * 32) / (0.21 * 32 + 0.79 * 28) / (1 + $moisture_air);
        $x_N2 = (0.79 * 28) / (0.21 * 32 + 0.79 * 28) / (1 + $moisture_air);
        $x_H2O = $moisture_air / (1 + $moisture_air);
        // Avoid division by zero if all fractions are zero (unlikely but safe)
        $den = ($x_O2 / 32) + ($x_N2 / 28) + ($x_H2O / 18);
        if ($den == 0) throw new Exception("Error calculating gas properties: Denominator is zero.");
        $res['y_O2'] = ($x_O2 / 32) / $den;
        $res['y_N2'] = ($x_N2 / 28) / $den;
        $res['y_H2O'] = ($x_H2O / 18) / $den;
        $res['PM'] = $res['y_O2'] * 32 + $res['y_N2'] * 28 + $res['y_H2O'] * 18;
        return $res;
    }

    public function calculateMaterial($mat) {
        $res = [];
        $res['T_solids_C'] = (float)$mat['T_solids_C'];
        $res['ms_kgh'] = (float)$mat['ms_tph'] * self::TPH_TO_KGH;
        $res['ms_lbh'] = $res['ms_kgh'] * self::KG_TO_LB;
        $res['ms_lbs'] = $res['ms_lbh'] / 3600;
        return $res;
    }

    public function calculatePipe($pipe) {
        $res = [];
        $d_in = (float)$pipe['D_in'];
        if ($d_in <= 0) throw new Exception("Pipe diameter must be positive.");
        $res['D_mm'] = self::diam_in_to_mm($d_in);
        $res['D_ft'] = self::diam_in_to_ft($d_in);
        $res['A_m2'] = self::area_m2_from_dmm($res['D_mm']);
        $res['A_ft2'] = self::area_ft2_from_dft($res['D_ft']);
        $res['pipe_roughness'] = (float)$pipe['pipe_roughness']; // Store roughness for f calculation
        return $res;
    }

    public function calculateFlow($flow, $atm, $mat, $pipe) {
        $res = [];
        $vin_ms = (float)$flow['Vin_ms'];
        $tin_gas_c = (float)$flow['Tin_gas_C'];
        $preq_bar = (float)$flow['Preq_bar'];
        if ($vin_ms <= 0) throw new Exception("Initial velocity must be positive.");

        // Initial, unadjusted velocity from user input
        $res['Vin_fts'] = $vin_ms * self::MPS_TO_FTS;

        $R_for_Tequ_calc = 11.0;
        $Tequ_C_denominator = (1 * 0.24 + $R_for_Tequ_calc * 0.2213);
        if ($Tequ_C_denominator == 0) throw new Exception("Error calculating equilibrium temperature.");
        $Tequ_C = (1 * 0.24 * $tin_gas_c + $R_for_Tequ_calc * 0.2213 * $mat['T_solids_C']) / $Tequ_C_denominator;
        $res['Tequ_C'] = $Tequ_C;
        $res['Treq_C'] = $Tequ_C;

        // Ensure Patm_bar is not zero
        if ($atm['Patm_bar'] <= 0) throw new Exception("Atmospheric pressure calculation resulted in non-positive value.");
        
        $k_from_c_tamb = self::k_from_c($atm['Tamb_C']);
        $k_from_c_treq = self::k_from_c($res['Treq_C']);
        if ($k_from_c_treq == 0) throw new Exception("Required temperature cannot be absolute zero.");
        
        $res['ro_req_lbft3'] = $atm['ro_gas_us'] * (($atm['Patm_bar'] + $preq_bar) / $atm['Patm_bar']) * ($k_from_c_tamb / $k_from_c_treq);
        
        if ($res['ro_req_lbft3'] <= 0) {
             throw new Exception("Required gas density calculation resulted in non-positive value. Check required pressure.");
        }

        // Temperature-adjusted velocity (needed for some outputs, but not for initial R_loading/mg_lbs)
        $res['V_ms'] = $vin_ms * (self::k_from_c($tin_gas_c)) / $k_from_c_treq;
        $res['V_fts'] = $res['V_ms'] * self::MPS_TO_FTS;

        // R_loading and mg_lbs should be calculated with the initial, unadjusted velocity and required density
        $denominator_R = $res['ro_req_lbft3'] * $pipe['A_ft2'] * $res['Vin_fts'];
        if ($denominator_R == 0) throw new Exception("Error calculating R_loading: Denominator is zero (check velocity or diameter).");
        $res['R_loading'] = $mat['ms_lbs'] / $denominator_R;
        $res['mg_lbs'] = $res['ro_req_lbft3'] * $pipe['A_ft2'] * $res['Vin_fts'];

        $res['Preq_PSIG'] = $preq_bar * self::BAR_TO_PSI;

        // Calculate standard flows
        $Q_m3h = $vin_ms * $pipe['A_m2'] * 3600; // Actual volumetric flow at start
        if (self::P_NORM == 0) throw new Exception("Error calculating standard flow: P_NORM is zero.");
        $res['Q_std_m3h'] = $Q_m3h * (($preq_bar + $atm['Patm_bar']) / self::P_NORM) * ((273.15 + 15) / $k_from_c_treq);
        $res['Q_stf_scfm'] = $res['Q_std_m3h'] * self::CFM_FROM_M3H;


        return $res;
    }

    public function calculateSegmentation($segments) {
        $res = ['sections' => [], 'total_m' => 0, 'total_ft' => 0];
        $cumulative_ft = 0;
        $section_number = 1;

        $getAccessoryEquivalentLength = function ($accessory_name) {
            if (empty($accessory_name)) return 0;
            
            // Lógica "hard-coded" en lugar de consulta a BD
            $accessory_lower = strtolower($accessory_name);
            if (strpos($accessory_lower, 'diverter valve 30') !== false) return 10;
            if (strpos($accessory_lower, 'diverter valve 45') !== false) return 20;
            if (strpos($accessory_lower, 'stainless steel') !== false) return 3;
            if (strpos($accessory_lower, 'rubber or vinyl hose') !== false) return 5;
            
            // 2. Si no es un accesorio nombrado, verificar si es un ángulo numérico
            $angle = floatval($accessory_name);
            if ((is_numeric($accessory_name) || $angle != 0) && $angle > 0) {
                 return 20 * $angle / 90;
            }
            
            return 0; // Return 0 if not a recognized accessory or valid angle
        };

        foreach ($segments as $tramoIndex => $tramo) {
            if (!isset($tramo['length']) || !is_numeric($tramo['length']) || $tramo['length'] < 0) {
                throw new Exception("Invalid length provided for segment " . ($tramoIndex + 1));
            }
            $L_tramo_ft = (float)$tramo['length'] * self::M_TO_FT;
            $remaining_ft = $L_tramo_ft;
            $segment_length_ft = 5;

            while ($remaining_ft > 1e-6) { // Use tolerance for floating point comparison
                $current_segment_ft = min($remaining_ft, $segment_length_ft);
                $cumulative_ft += $current_segment_ft;
                $res['sections'][] = [
                    'section_number' => $section_number++,
                    'component' => "pipe",
                    'orientation' => $tramo['orientation'],
                    'EQ_Length_ft' => $current_segment_ft,
                    'EQ_Cumulative_ft' => $cumulative_ft,
                ];
                $remaining_ft -= $current_segment_ft;
            }

            if (!empty($tramo['accessory'])) {
                $eq_length_ft = $getAccessoryEquivalentLength($tramo['accessory']);
                if ($eq_length_ft > 0) {
                    $cumulative_ft += $eq_length_ft;
                    $accessoryOrientation = 'Vertical'; // Default for safety unless proven Horizontal continuity
                    $nextTramo = isset($segments[$tramoIndex + 1]) ? $segments[$tramoIndex + 1] : null;
                    // Accessory is Horizontal ONLY if previous and next segments are Horizontal
                    if ($tramo['orientation'] === 'Horizontal' && $nextTramo && $nextTramo['orientation'] === 'Horizontal') {
                        $accessoryOrientation = 'Horizontal';
                    }
                    $res['sections'][] = [
                        'section_number' => $section_number++,
                        'component' => $tramo['accessory'],
                        'orientation' => $accessoryOrientation,
                        'EQ_Length_ft' => $eq_length_ft,
                        'EQ_Cumulative_ft' => $cumulative_ft,
                    ];
                }
            }
        }
        $res['total_m'] = array_reduce($segments, function($sum, $s) { return $sum + (float)$s['length']; }, 0);
        $res['total_ft'] = $cumulative_ft;
        return $res;
    }
    
    public function calculatePressureDrop($data) {
        extract($data); // Extracts segmentation, flow, pipe, material, atmospheric, inputs
        $sectionsData = [];
        $prev_section = null;
        $R_constant = $flow['R_loading'];
        $oversizing_param = isset($inputs['pipe']['oversizing_param']) ? (float)$inputs['pipe']['oversizing_param'] : 4.0;

        // Calculate Reynolds number (Re) and Friction factor (f) once before the loop
        $D_ft = $pipe['D_ft'];
        $Vin_fts_initial = $flow['Vin_fts'];
        $ro_req_lbft3 = $flow['ro_req_lbft3'];
        $pipe_roughness = $pipe['pipe_roughness'];

        if (self::REYNOLDS_CONSTANT == 0) throw new Exception("Reynolds constant cannot be zero.");
        $Re = ($Vin_fts_initial * $ro_req_lbft3 * $D_ft) / self::REYNOLDS_CONSTANT;

        $friction_factor = 0; // Default value
        if ($Re > 0 && $D_ft > 0) {
            $log_arg = ($pipe_roughness / (3.7 * $D_ft)) + (7 / $Re);
            if ($log_arg > 0) {
                $ln_val = log($log_arg); // Natural logarithm (base e)
                if ($ln_val != 0) {
                    $friction_factor = 0.331 / pow($ln_val, 2);
                } else {
                     $friction_factor = 0.005; // Fallback for ln(1) = 0
                }
            } else {
                 throw new Exception("Cannot calculate friction factor: Invalid argument for logarithm (<=0). Check roughness and Reynolds number.");
            }
        } else {
             $friction_factor = 0.005; // Default guess if Re or D_ft is zero
        }

        if ($friction_factor <= 0) {
             throw new Exception("Calculated friction factor is non-positive. Check input parameters.");
        }


        foreach ($segmentation['sections'] as $i => $section) {
            $s = []; // current_section_res

            $s['section_number'] = $section['section_number'];
            $s['orientation'] = $section['orientation'];
            $s['L_ft'] = $section['EQ_Length_ft'];
            $s['D_ft'] = $D_ft; // Use pre-calculated D_ft
            $s['f'] = $friction_factor; // Use pre-calculated friction_factor
            $s['W'] = $material['ms_lbs'] / $pipe['A_ft2'];
            $s['R'] = $R_constant;

            if ($i === 0) {
                $s['Pin_psia'] = $atmospheric['Patm_PSI'] + $flow['Preq_PSIG'];
                $s['roin_gas'] = $ro_req_lbft3; // Use pre-calculated density
                $s['Vin_fts'] = $Vin_fts_initial; // Use initial unadjusted velocity
            } else {
                if ($prev_section === null) throw new Exception("Error interno: prev_section no está definido para i > 0.");
                $s['Pin_psia'] = $prev_section['Pout_psia'];
                $s['roin_gas'] = $prev_section['roout_gas'];
                $s['Vin_fts'] = $prev_section['Vout_fts'];
            }

            // Ensure Vin_fts is positive for calculations relying on it
            if ($s['Vin_fts'] <= 0) {
                 throw new Exception("Gas velocity became non-positive in section {$s['section_number']}. Calculation cannot continue.");
            }


            $s['Vp_fts'] = 0.8 * $s['Vin_fts'];
            $Vin_ms = $s['Vin_fts'] / self::MPS_TO_FTS;
            $Vp_ms = 0.8 * $Vin_ms;

            // Avoid division by zero for Fr
             $s['Fr'] = ($pipe['D_mm'] > 0) ? ($Vp_ms / pow(9.81 * ($pipe['D_mm'] / 1000), 0.5)) : 0;

            // Calculate K using the oversizing parameter
            $s['K'] = ($s['R'] <= 0 || $s['Fr'] <= 0) ? 0 : (87 / (pow($s['R'], 0.4) * pow($s['Fr'], 2))) * $oversizing_param;

            $s['dZ_ft'] = ($section['orientation'] === 'Vertical') ? $section['EQ_Length_ft'] : 0;

            // Calculate pressure drop components, ensuring denominators are not zero
            $Pdrop_flowgas = (9266 * $s['D_ft'] > 0) ? (4 * $s['f'] * $s['L_ft'] * $s['roin_gas'] * pow($s['Vin_fts'], 2)) / (9266 * $s['D_ft']) : 0;

             $Pdrop_solidacc = ($i === 0) ? ($s['W'] * $s['Vp_fts'] / 4640) : ($s['W'] * ($s['Vp_fts'] - $prev_section['Vp_fts']) / 4640);


            $Pdrop_flowsol = $Pdrop_flowgas * $s['K'] * $s['R'];
            $Pdrop_elvgas = $s['dZ_ft'] * $s['roin_gas'] / (144 * self::GC);

            $Pdrop_elvsol_denom = 144 * $s['Vp_fts'] * self::GC;
            $Pdrop_elvsol = ($s['dZ_ft'] > 0 && $Pdrop_elvsol_denom != 0) ? ($s['dZ_ft'] * $s['W'] * self::G / $Pdrop_elvsol_denom) : 0;

            $Pdrop_misc = ($i === 0) ? 0.5 : 0;

            $s['Pdrop_psi'] = $Pdrop_flowgas + $Pdrop_solidacc + $Pdrop_flowsol + $Pdrop_elvgas + $Pdrop_elvsol + $Pdrop_misc;

            $s['Pout_psia'] = $s['Pin_psia'] - $s['Pdrop_psi'];
            if ($s['Pout_psia'] <= 0) {
                // Lanzar una excepción específica que el solver pueda capturar
                throw new Exception("La presión de salida se volvió no física (<= 0) en la sección {$s['section_number']}.");
            }

            // Avoid division by zero for Vout_fts
             $s['Vout_fts'] = ($s['Pout_psia'] > 0 && $s['Pin_psia'] > 0) ? ($s['Vin_fts'] * $s['Pin_psia'] / $s['Pout_psia']) : 0;
            if ($s['Vout_fts'] < 0) $s['Vout_fts'] = 0; // Prevent negative velocity if pressure flips unexpectedly

            // Avoid division by zero for roout_gas
            $roout_gas_denom = $pipe['A_ft2'] * $s['Vout_fts'];
            $s['roout_gas'] = ($roout_gas_denom > 0) ? ($flow['mg_lbs'] / $roout_gas_denom) : 0;
             if ($s['roout_gas'] < 0) $s['roout_gas'] = 0; // Safety check

            // Assign drop components for rendering
            $s['Pdrop_flowgas'] = $Pdrop_flowgas;
            $s['Pdrop_solidacc'] = $Pdrop_solidacc;
            $s['Pdrop_flowsol'] = $Pdrop_flowsol;
            $s['Pdrop_elvgas'] = $Pdrop_elvgas;
            $s['Pdrop_elvsol'] = $Pdrop_elvsol;
            $s['Pdrop_misc'] = $Pdrop_misc;

            $sectionsData[] = $s;
            $prev_section = $s;
        }

        $dP_psi_total = array_reduce($sectionsData, function($sum, $s) { return $sum + $s['Pdrop_psi']; }, 0);
        $dP_bar_total = $dP_psi_total / self::BAR_TO_PSI;

        // Get the final velocity from the last segment
        $final_Vout_fts = 0;
        $final_Vout_ms = 0;
        if (!empty($sectionsData)) {
            $last_section = end($sectionsData);
            $final_Vout_fts = $last_section['Vout_fts'];
            $final_Vout_ms = $final_Vout_fts / self::MPS_TO_FTS;
        }

        return [
            'sectionsData' => $sectionsData,
            'dP_psi_total' => $dP_psi_total,
            'dP_bar_total' => $dP_bar_total,
            'final_Vout_fts' => $final_Vout_fts,
            'final_Vout_ms' => $final_Vout_ms,
            'calculated_f' => $friction_factor, // Return calculated f
            'calculated_re' => $Re // Return calculated Re
        ];
    }

    /**
     * Finds the equilibrium pressure for a given set of inputs, diameter, and velocity.
     * It iteratively runs the simulation until Preq_bar (input) == dP_bar_total (output).
     */
    private function findEquilibriumPressure($inputs, $D_in, $Vin_ms, $constraints, $t) {
        $max_iterations = 20;
        $tolerance = 0.001; // Tolerance in bar
        $Preq_bar_guess = 0.5; // Initial guess
        $last_error = 0;
        $last_guess = 0;

        // Prepare a simulation-ready input object
        $sim_inputs = $inputs;
        $sim_inputs['pipe']['D_in'] = $D_in;
        $sim_inputs['flow']['Vin_ms'] = $Vin_ms;
        
        for ($i = 0; $i < $max_iterations; $i++) {
            $sim_inputs['flow']['Preq_bar'] = $Preq_bar_guess;
            
            try {
                $result = $this->run($sim_inputs);
                
                if (!$result['success']) {
                    // This catches calculation logic failures (e.g., division by zero before Pdrop)
                    return ['status' => 'Fail', 'reason' => $t['solver_fail_unstable'] . ': ' . $result['error']];
                }

            } catch (Exception $e) {
                // *** ESTA ES LA LÓGICA CORREGIDA ***
                // Captura la excepción de "presión no física"
                if (strpos($e->getMessage(), 'no física') !== false) {
                    // Hacer un salto más grande para converger más rápido en sistemas largos
                    $Preq_bar_guess = $Preq_bar_guess + 0.1;
                    $last_error = 0; // Resetear el solver
                    $last_guess = 0;
                    if ($Preq_bar_guess > 10) { // Failsafe para evitar bucles infinitos
                         return ['status' => 'Fail', 'reason' => $t['solver_fail_unstable'] . ': Presión > 10 bar'];
                    }
                    continue; // Probar la siguiente iteración con la nueva suposición
                }
                // Si es otro tipo de error (ej. división por cero), es un fallo irrecuperable
                return ['status' => 'Fail', 'reason' => $t['solver_fail_unstable'] . ': ' . $e->getMessage()];
            }


            $res = $result['results'];

            // Check constraints *before* checking convergence
            if ($res['flow']['R_loading'] > $constraints['max_r_loading']) {
                // Esta comprobación ahora se hace *después* de que la presión sea válida
                return ['status' => 'Fail', 'reason' => $t['solver_fail_r_loading'] . ' (' . round($res['flow']['R_loading'], 2) . ')'];
            }
            if ($res['pressureDrop']['final_Vout_ms'] >= $constraints['max_vout_ms']) {
                return ['status' => 'Fail', 'reason' => $t['solver_fail_vout'] . ' (' . round($res['pressureDrop']['final_Vout_ms'], 2) . ' m/s)'];
            }

            // Now check convergence
            $dP_bar_total = $res['pressureDrop']['dP_bar_total'];
            $error = $dP_bar_total - $Preq_bar_guess;

            if (abs($error) < $tolerance) {
                // SUCCESS! We found the equilibrium pressure.
                return [
                    'status' => 'Success',
                    'solution' => [
                        'D_in' => $D_in,
                        'Vin_ms' => $Vin_ms,
                        'Vout_ms' => $res['pressureDrop']['final_Vout_ms'],
                        'R_loading' => $res['flow']['R_loading'],
                        'Preq_bar' => $dP_bar_total // The final, correct pressure
                    ]
                ];
            }

            // Not converged yet, make a new guess
            $last_guess_temp = $Preq_bar_guess;
            
            // Damped fixed-point iteration (smarter guess)
            if ($i > 0 && abs($error - $last_error) > 1e-5) {
                // Secant method approximation to converge faster
                $delta_guess = $Preq_bar_guess - $last_guess;
                if (abs($delta_guess) < 1e-6) $delta_guess = 0.1; // avoid division by zero
                $error_diff = $error - $last_error;
                if (abs($error_diff) < 1e-6) $error_diff = 0.1; // avoid division by zero
                
                $Preq_bar_guess = $Preq_bar_guess - $error * ($delta_guess) / ($error_diff);
            } else {
                // Simple step (damped)
                $Preq_bar_guess = $Preq_bar_guess + $error * 0.8; // Move 80% of the way to the answer
            }
            
            // Safety check for guess
            if ($Preq_bar_guess < 0.01) $Preq_bar_guess = $dP_bar_total + 0.1; // boost if it goes too low
            if ($Preq_bar_guess > 10) return ['status' => 'Fail', 'reason' => 'La presión de equilibrio supera los 10 bar.']; // Failsafe

            // Save last state for secant method
            $last_guess = $last_guess_temp;
            $last_error = $error;
        }

        // If loop finishes without convergence
        return ['status' => 'Fail', 'reason' => $t['solver_fail_unstable']];
    }
    

    // --- NUEVA FUNCIÓN: SUGGEST PARAMETERS ---
    public function suggestParameters($inputs, $t) { // $t (translations) added for error messages
        try {
            $report = [];
            $standard_diameters = [4, 5, 6, 8, 10, 12, 14, 16, 18]; // In inches
            
            $constraints = [
                'min_vin_ms' => 9.0,
                'max_vin_ms' => 20.0,
                'max_vout_ms' => 35.0,
                'max_r_loading' => 15.0
            ];

            // 2. Outer loop: Iterate Diameters
            foreach ($standard_diameters as $D_in) {
                $best_solution_for_this_diameter = null;
                $failure_reason = null;

                // 3. Inner loop: Iterate Velocities
                for ($Vin_ms = $constraints['min_vin_ms']; $Vin_ms <= $constraints['max_vin_ms']; $Vin_ms += 0.5) {
                    
                    $result = $this->findEquilibriumPressure($inputs, $D_in, $Vin_ms, $constraints, $t);

                    if ($result['status'] === 'Success') {
                        $best_solution_for_this_diameter = $result;
                        break; // Found the best (lowest Vin) for this diameter.
                    }
                    
                    // Store the first failure reason encountered for this diameter
                    if (!$failure_reason) {
                        $failure_reason = $result;
                    }

                    // Check the *type* of failure
                    if (strpos($result['reason'], $t['solver_fail_r_loading']) !== false) {
                        continue; // This velocity is too low, try next higher velocity
                    }
                    
                    if (strpos($result['reason'], $t['solver_fail_vout']) !== false) {
                         // Higher velocities will only make Vout worse. Stop testing this diameter.
                        break;
                    }
                    
                    if(strpos($result['reason'], $t['solver_fail_unstable']) !== false) {
                        // If it's unstable (e.g. pressure check), stop testing this velocity.
                        // The corrected logic in findEquilibriumPressure should handle this better,
                        // but as a failsafe, we stop.
                        break; 
                    }
                }
                
                // 4. Store the result for this diameter
                if ($best_solution_for_this_diameter) {
                    $report[] = $best_solution_for_this_diameter;
                } else if ($failure_reason) {
                    // If we finished the loop and never succeeded, report the first failure
                    $failure_reason['D_in'] = $D_in;
                    $report[] = $failure_reason;
                } else {
                    // This happens if *all* velocities (9 to 20) failed dueto R_loading
                    $report[] = ['D_in' => $D_in, 'status' => 'Fail', 'reason' => $t['solver_fail_r_loading'] . ' (>' . $constraints['max_r_loading'] . ' ' . $t['at_max_velocity'] . ')'];
                }
            }
            
            return ['success' => true, 'report' => $report];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    // --- MÉTODO PRINCIPAL ---
    public function run($inputs) {
        try {
            // Validate inputs early
             if (!isset($inputs['pipe']['oversizing_param']) || !is_numeric($inputs['pipe']['oversizing_param']) || $inputs['pipe']['oversizing_param'] < 1) {
                 throw new Exception("Parámetro de sobredimensionamiento inválido. Debe ser un número >= 1.");
             }
             if (!isset($inputs['pipe']['pipe_roughness']) || !is_numeric($inputs['pipe']['pipe_roughness']) || $inputs['pipe']['pipe_roughness'] < 0) { // Allow 0 for smooth pipes
                 throw new Exception("Rugosidad de tubería inválida. Debe ser un número positivo o cero.");
             }

            $gas = $this->calculateGas($inputs['gas']);
            $atm = $this->calculateAtmospheric($inputs['atmospheric'], $gas);
            $mat = $this->calculateMaterial($inputs['material']);
            $pipe = $this->calculatePipe($inputs['pipe']);
            $flow = $this->calculateFlow($inputs['flow'], $atm, $mat, $pipe);
            $segmentation = $this->calculateSegmentation($inputs['segments']);

            $pressureDropResults = $this->calculatePressureDrop([
                'segmentation' => $segmentation,
                'flow' => $flow,
                'pipe' => $pipe,
                'material' => $mat,
                'atmospheric' => $atm,
                'inputs' => $inputs // Pass all inputs
            ]);

            $results = [
                'inputs' => $inputs,
                'atmospheric' => $atm,
                'gas' => $gas,
                'material' => $mat,
                'pipe' => $pipe, // Contains D_mm, D_ft, A_m2, A_ft2, pipe_roughness
                'flow' => $flow, // Contains Vin_fts, R_loading, Treq_C, ro_req_lbft3, mg_lbs, Q_std_m3h, Q_stf_scfm, etc.
                'segmentation' => $segmentation,
                'pressureDrop' => $pressureDropResults // Contains sectionsData, totals, final velocities, calculated f and Re
            ];

            // Add extra data for summary view
            $results['summaryData'] = [
                'vin_ms' => (float)$inputs['flow']['Vin_ms'],
                'vin_fts' => $flow['Vin_fts'],
                'solids_material' => $inputs['material']['solids_material'],
                'ms_tph' => (float)$inputs['material']['ms_tph'],
                'final_temp_c' => $flow['Treq_C'],
                'final_temp_f' => self::c_to_f($flow['Treq_C']),
                'q_std_m3h' => $flow['Q_std_m3h'],
                'q_stf_scfm' => $flow['Q_stf_scfm'],
                'preq_psi' => $flow['Preq_PSIG'],
                'patm_psi' => $atm['Patm_PSI']
            ];

            return ['success' => true, 'results' => $results];
        } catch (Exception $e) {
            // Log the detailed error on the server if needed
            // error_log("Calculation Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}







