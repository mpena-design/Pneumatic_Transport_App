<?php
// Este archivo es incluido por index.php (el controlador), por lo que tiene acceso a las variables $t y $lang.
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['app_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-color: #2563eb; /* blue-600 */
            --primary-hover: #1d4ed8; /* blue-700 */
            --secondary-color: #4f46e5; /* indigo-600 */
            --secondary-hover: #4338ca; /* indigo-700 */
            --success-color: #16a34a; /* green-600 */
            --success-hover: #15803d; /* green-700 */
            --background-color: #f3f4f6; /* gray-100 */
            --card-background: #ffffff;
            --text-color: #1f2937; /* gray-800 */
            --muted-text-color: #6b7280; /* gray-500 */
            --border-color: #e5e7eb; /* gray-200 */
            --active-tab-border: #2563eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            max-width: 1400px;
        }

        #lang-switcher a {
            font-weight: 600;
            color: var(--muted-text-color);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
         #lang-switcher a.active {
            color: var(--primary-color);
            background-color: #ddeafe;
        }


        .tab-button {
            padding: 0.75rem 1.5rem;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
            color: var(--muted-text-color);
        }

        .tab-button.active, .tab-button:hover {
            color: var(--primary-color);
            border-bottom-color: var(--active-tab-border);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .form-input {
            border-color: var(--border-color);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.4);
            outline: none;
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 600;
            padding: 0.625rem 1.25rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary { background-color: var(--primary-color); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: var(--secondary-color); color: white; }
        .btn-secondary:hover { background-color: var(--secondary-hover); }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-success:hover { background-color: var(--success-hover); }
        .btn-outline { background-color: transparent; border: 1px solid var(--border-color); color: var(--muted-text-color); }
        .btn-outline:hover { background-color: #f9fafb; color: var(--text-color); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .results-panel {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }

        .summary-gauge-card {
            background-color: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        .gauge-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        .gauge-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        .gauge-sub-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--muted-text-color);
        }
        .summary-info-card {
            background-color: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        .summary-info-card .label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--muted-text-color);
            margin-bottom: 0.25rem;
        }
        .summary-info-card .value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
        }
        .summary-info-card .sub-value {
            font-size: 0.875rem;
            color: var(--muted-text-color);
        }

        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
            color: var(--primary-color);
            margin-left: 0.25rem;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px 12px;
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            margin-left: -125px; /* Half of width */
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
            font-weight: normal;
            pointer-events: none; /* Allows hover over tooltip itself */
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%; /* At the bottom of the tooltip */
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 50;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            width: 90%;
            max-width: 500px;
            transform: scale(0.95);
            transition: transform 0.2s;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }


        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f3f4f6; font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        
        .solver-table-row-fail { background-color: #fee2e2; } /* bg-red-100 */
        .solver-table-row-success { background-color: #dcfce7; } /* bg-green-100 */

        .error-panel { padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; background-color: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; }
        .success-panel { padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; background-color: #dcfce7; border: 1px solid #86efac; color: #166534; }
    </style>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
</head>

<body class="p-4 sm:p-6 lg:p-8">
    <div class="container mx-auto">
        <header class="mb-6">
            <div id="lang-switcher" class="flex justify-end gap-2 mb-2 text-sm">
                <a href="?lang=es" class="<?= $lang === 'es' ? 'active' : '' ?>">Español</a>
                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">English</a>
            </div>
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-800"><?= htmlspecialchars($t['app_title']) ?></h1>
                <p class="mt-2 text-lg text-gray-600"><?= htmlspecialchars($t['app_subtitle']) ?></p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Columna de Entradas -->
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <form id="calc-form">
                    <!-- Navegación de Pestañas -->
                    <div id="tab-nav" class="border-b border-gray-200 mb-6 flex">
                        <button type="button" class="tab-button active" data-tab="tab-1"><?= htmlspecialchars($t['tab1_title']) ?></button>
                        <button type="button" class="tab-button" data-tab="tab-2"><?= htmlspecialchars($t['tab2_title']) ?></button>
                        <button type="button" class="tab-button" data-tab="tab-3"><?= htmlspecialchars($t['tab3_title']) ?></button>
                    </div>

                    <!-- Contenido de Pestañas -->
                    <div id="tab-content">
                        <div id="tab-1" class="tab-panel active space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['section1_title']) ?></h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="location" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['location']) ?></label>
                                        <input type="text" id="location" class="form-input mt-1 block w-full rounded-md" value="Proyecto X" required>
                                    </div>
                                    <div>
                                        <label for="height_m" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['height']) ?></label>
                                        <input type="number" id="height_m" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="1500" required>
                                    </div>
                                    <div>
                                        <label for="humidity_pct" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['humidity']) ?></label>
                                        <input type="number" id="humidity_pct" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="60" required>
                                    </div>
                                    <div>
                                        <label for="tamb_c" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['ambient_temp']) ?></label>
                                        <input type="number" id="tamb_c" step="any" class="form-input mt-1 block w-full rounded-md" value="25" required>
                                    </div>
                                </div>
                            </div>
                             <div>
                                <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['section2_title']) ?></h3>
                                <div class="pt-4">
                                    <label for="moisture_air" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['specific_humidity']) ?></label>
                                    <input type="number" id="moisture_air" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="0.02" required>
                                </div>
                            </div>
                        </div>

                        <div id="tab-2" class="tab-panel space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['section3_title']) ?></h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-1">
                                        <label for="solids_material" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['solid_material']) ?></label>
                                        <input type="text" id="solids_material" class="form-input mt-1 block w-full rounded-md" value="Cemento" required>
                                    </div>
                                    <div>
                                        <label for="ms_tph" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['mass_flow']) ?></label>
                                        <input type="number" id="ms_tph" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="12" required>
                                    </div>
                                    <div>
                                        <label for="t_solids_c" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['solid_temp']) ?></label>
                                        <input type="number" id="t_solids_c" step="any" class="form-input mt-1 block w-full rounded-md" value="60" required>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['section4_title']) ?></h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="pipe_material" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['pipe_material']) ?></label>
                                        <input type="text" id="pipe_material" class="form-input mt-1 block w-full rounded-md" value="Steel, schedule 40 pipe, internally score" required>
                                    </div>
                                    <div>
                                        <label for="pipe_roughness" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['roughness']) ?></label>
                                        <input type="number" id="pipe_roughness" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="0.0050" required>
                                    </div>
                                    <div>
                                        <label for="d_in" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['internal_diameter']) ?></label>
                                        <input type="number" id="d_in" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="6" required>
                                    </div>
                                     <div class="sm:col-span-2">
                                        <label for="oversizing_param" class="block text-sm font-medium text-gray-700">
                                            <?= htmlspecialchars($t['oversizing_param']) ?>
                                            <span class="tooltip">(?)
                                                <span class="tooltiptext"><?= htmlspecialchars($t['oversizing_tooltip']) ?></span>
                                            </span>
                                        </label>
                                        <input type="number" id="oversizing_param" min="1" step="0.1" class="form-input mt-1 block w-full rounded-md" value="4" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="tab-3" class="tab-panel space-y-6">
                             <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold"><?= htmlspecialchars($t['section5_title']) ?></h3>
                                    <button type="button" id="suggest-params-btn" class="btn btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM9 7a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zm6 4a1 1 0 100 2h1a1 1 0 100-2h-1zM6 9a1 1 0 100 2h1a1 1 0 100-2H6zm7-5a1 1 0 100 2h1a1 1 0 100-2h-1zm-3 8a1 1 0 100 2h1a1 1 0 100-2h-1zm-4 4a1 1 0 100 2h1a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                        <?= htmlspecialchars($t['suggest_parameters']) ?>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="vin_ms" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['initial_velocity']) ?></label>
                                        <input type="number" id="vin_ms" min="0" step="any" class="form-input mt-1 block w-full rounded-md" value="12" required>
                                    </div>
                                    <div>
                                        <label for="tin_gas_c" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['initial_gas_temp']) ?></label>
                                        <input type="number" id="tin_gas_c" step="any" class="form-input mt-1 block w-full rounded-md" value="20" required>
                                    </div>
                                    <div>
                                        <label for="preq_bar" class="block text-sm font-medium text-gray-700"><?= htmlspecialchars($t['required_pressure']) ?></label>
                                        <input type="number" id="preq_bar" step="any" class="form-input mt-1 block w-full rounded-md" value="0.5" required>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['section6_title']) ?></h3>
                                <div id="pipe-segments-container" class="space-y-4"></div>
                                <button type="button" id="add-segment-btn" class="mt-4 btn btn-outline">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                                    <?= htmlspecialchars($t['add_segment']) ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-4 border-t border-gray-200 pt-6">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L8.586 6H4a1 1 0 000 2h4.586l-2.293 2.293a1 1 0 101.414 1.414l4-4a1 1 0 000-1.414l-4-4A1 1 0 007 2zM12 10a1 1 0 011-1h4a1 1 0 110 2h-4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                            <?= htmlspecialchars($t['calculate']) ?>
                        </button>
                        <button type="button" id="clear-form-btn" class="btn btn-outline"><?= htmlspecialchars($t['clear']) ?></button>
                        <button type="button" id="fill-example-btn" class="btn btn-secondary"><?= htmlspecialchars($t['fill_example']) ?></button>
                    </div>
                </form>
            </div>

            <!-- Columna de Resultados -->
            <div class="results-panel">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 space-y-8">
                    <div id="error-container"></div>
                    <div id="message-container"></div> <!-- Para mensajes de éxito -->

                    <div class="flex flex-wrap gap-4">
                         <!-- Botón Guardar Eliminado -->
                         <button id="export-csv-btn" class="btn btn-outline" disabled><?= htmlspecialchars($t['export_csv']) ?></button>
                         <button id="export-xlsx-btn" class="btn btn-outline" disabled><?= htmlspecialchars($t['export_excel']) ?></button>
                         <button id="export-pdf-btn" class="btn btn-outline" disabled><?= htmlspecialchars($t['export_pdf']) ?></button>
                    </div>

                    <div id="results-container" class="space-y-8">
                        <div class="text-center py-10 text-gray-500">
                             <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <p class="mt-2 font-medium"><?= htmlspecialchars($t['results_placeholder_title']) ?></p>
                            <p class="text-sm"><?= htmlspecialchars($t['results_placeholder_subtitle']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- Modal para Guardar Cálculo Eliminado -->
    
    <!-- Modal para Sugerir Parámetros -->
    <div id="solver-modal-overlay" class="modal-overlay">
        <div class="modal-content !max-w-4xl"> <!-- !max-w-4xl makes it wider -->
            <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($t['solver_modal_title']) ?></h3>
            <div id="solver-modal-body">
                <!-- Loading state -->
                <div id="solver-loading" class="text-center p-8">
                    <div class="flex justify-center items-center">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="mt-3 font-medium text-center"><?= htmlspecialchars($t['solver_loading']) ?></p>
                </div>
                <!-- Results table -->
                <div id="solver-results" class="hidden overflow-x-auto rounded-lg border max-h-[60vh]">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-100">
                            <tr>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_diameter_in']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_status']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_details_reason']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_vin_ms']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_vout_ms']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_r_loading']) ?></th>
                                <th class="p-3 text-left"><?= htmlspecialchars($t['th_preq_bar']) ?></th>
                                <th class="p-3"></th>
                            </tr>
                        </thead>
                        <tbody id="solver-results-tbody">
                            <!-- Rows will be injected here by JS -->
                        </tbody>
                    </table>
                </div>
                <!-- No solutions message -->
                <div id="solver-no-solutions" class="hidden text-center p-8 text-gray-500">
                    <p><?= htmlspecialchars($t['solver_no_solutions']) ?></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-4">
                <button type="button" id="cancel-solver-btn" class="btn btn-outline"><?= htmlspecialchars($t['close']) ?></button>
            </div>
        </div>
    </div>
    
    <script>
        // Pasar traducciones a JavaScript
        const T = <?= json_encode($t) ?>;
    </script>
    <script src="app.js"></script>
    <datalist id="accessory-list">
        <option value="Diverter Valve 30°"></option>
        <option value="Diverter Valve 45°"></option>
        <option value="STAINLESS STEEL WITH LINED INTERIOR"></option>
        <option value="RUBBER OR VINYL HOSE"></option>
    </datalist>
</body>
</html>



















