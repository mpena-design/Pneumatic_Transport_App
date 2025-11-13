// --- MVC: MODEL (Constants, Utilities, Calculation Logic is now on Server) ---

const APP_CONSTANTS = { // Constants needed for display
    PI: 3.141592653589793,
    gc: 32.174,
    g: 32.2,
    P_norm: 1.01,
    T_norm: 0,
};

const APP_UTILS = { // Utilities / Converters needed for display
    KG_M3_TO_LB_FT3: 0.0624279606,
    BAR_to_PSI: 14.5037738,
    format: (num, dec = 3) => {
        if (typeof num !== 'number' || isNaN(num)) return '---';
        return num.toFixed(dec);
    },
};


// --- MVC: VIEW (DOM Manipulation and Rendering) ---

const view = {
    renderResults(results) {
        const container = document.getElementById('results-container');
        container.innerHTML = `
            ${this.renderSummaryCards(results)}
            ${this.renderSingleTable(T.norm_conditions_title, [
                { key: 'P_norm', value: APP_UTILS.format(APP_CONSTANTS.P_norm, 2), unit: 'bar' },
                { key: 'T_norm', value: APP_UTILS.format(APP_CONSTANTS.T_norm, 0), unit: '°C' },
            ])}
            ${this.renderSingleTable(T.atmospheric_results_title, [
                { key: 'Tamb_F', value: APP_UTILS.format(results.atmospheric.Tamb_F, 2), unit: '°F' },
                { key: 'Patm_mbar', value: APP_UTILS.format(results.atmospheric.Patm_mbar, 2), unit: 'mbar' },
                { key: 'Patm_PSI', value: APP_UTILS.format(results.atmospheric.Patm_PSI, 3), unit: 'PSI' },
                { key: 'ro_gas_us', value: APP_UTILS.format(results.atmospheric.ro_gas_us, 4), unit: 'lb/ft³' },
            ])}
            ${this.renderSingleTable(T.gas_results_title, [
                { key: 'y_O2', value: APP_UTILS.format(results.gas.y_O2 * 100, 2), unit: '%' },
                { key: 'y_N2', value: APP_UTILS.format(results.gas.y_N2 * 100, 2), unit: '%' },
                { key: 'y_H2O', value: APP_UTILS.format(results.gas.y_H2O * 100, 2), unit: '%' },
                { key: 'PM', value: APP_UTILS.format(results.gas.PM, 2), unit: 'g/mol' },
            ])}
             ${this.renderSingleTable(T.material_results_title, [
                { key: 'ms_kgh', value: APP_UTILS.format(results.material.ms_kgh, 2), unit: 'kg/h' },
                { key: 'ms_lbh', value: APP_UTILS.format(results.material.ms_lbh, 2), unit: 'lb/h' },
                { key: 'ms_lbs', value: APP_UTILS.format(results.material.ms_lbs, 4), unit: 'lb/s' },
            ])}
            ${this.renderSingleTable(T.pipe_results_title, [
                { key: 'D_mm', value: APP_UTILS.format(results.pipe.D_mm, 2), unit: 'mm' },
                { key: 'D_ft', value: APP_UTILS.format(results.pipe.D_ft, 4), unit: 'ft' },
                { key: 'A_m2', value: APP_UTILS.format(results.pipe.A_m2, 5), unit: 'm²' },
                { key: 'A_ft2', value: APP_UTILS.format(results.pipe.A_ft2, 5), unit: 'ft²' },
            ])}
            ${this.renderSingleTable(T.flow_results_title, [
                { key: 'R_loading', value: APP_UTILS.format(results.flow.R_loading, 3), unit: '' },
                { key: 'Tequ_C', value: APP_UTILS.format(results.flow.Tequ_C, 2), unit: '°C' },
                { key: 'V_fts (Adjusted)', value: APP_UTILS.format(results.flow.V_fts, 2), unit: 'ft/s' }, 
                { key: 'Vin_fts (Initial)', value: APP_UTILS.format(results.flow.Vin_fts, 2), unit: 'ft/s' }, 
                { key: 'ro_req_lbft3', value: APP_UTILS.format(results.flow.ro_req_lbft3, 4), unit: 'lb/ft³ (Req.)' },
                { key: T.reynolds_re, value: APP_UTILS.format(results.pressureDrop.calculated_re, 0), unit: '' },
                { key: T.friction_factor_f, value: APP_UTILS.format(results.pressureDrop.calculated_f, 5), unit: '' },
            ])}
            ${this.renderSegmentationTable(results.segmentation)}
            ${this.renderPressureDropTable(results.pressureDrop.sectionsData)}
        `;
    },

    renderSingleTable(title, data) {
        const rows = data.map(d => `
            <tr>
                <td class="font-medium">${d.key.replace(/_/g, ' ')}</td>
                <td>${d.value}</td>
                <td>${d.unit}</td>
            </tr>
        `).join('');
        return `
            <div>
                <h3 class="text-xl font-semibold mb-2">${title}</h3>
                <div class="overflow-x-auto rounded-lg border">
                    <table>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `;
    },

    renderSummaryCards(results) {
        const { pressureDrop, segmentation, summaryData } = results;

        const createGauge = (value, max, unit, label) => {
            const percentage = max > 0 ? Math.min(100, (value / max) * 100) : 0;
            const circumference = 2 * Math.PI * 45; // radius = 45
            const offset = circumference - (percentage / 100) * circumference;
            // Determine color based on percentage
            let colorClass = 'text-green-600';
            if (percentage > 50) colorClass = 'text-yellow-500';
            if (percentage > 85) colorClass = 'text-red-600';

            return `
                <div class="summary-gauge-card">
                    <div class="gauge-label">${label}</div>
                    <svg class="w-40 h-40 mx-auto" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" class="text-gray-200" stroke-width="10" fill="none" />
                        <circle cx="50" cy="50" r="45" class="${colorClass}" stroke-width="10" fill="none"
                                stroke-linecap="round"
                                transform="rotate(-90 50 50)"
                                style="stroke-dasharray: ${circumference}; stroke-dashoffset: ${offset}; transition: stroke-dashoffset 0.5s ease-in-out;"
                        />
                        <text x="50" y="50" text-anchor="middle" dy="0.3em" class="gauge-value fill-current text-gray-800">${APP_UTILS.format(value, 2)}</text>
                    </svg>
                    <div class="gauge-sub-value mt-2">${unit}</div>
                </div>
            `;
        };

        const createInfoCard = (label, value, subValue = '') => {
            return `
                <div class="summary-info-card">
                    <div class="label">${label}</div>
                    <div class="value">${value}</div>
                    ${subValue ? `<div class="sub-value">${subValue}</div>` : ''}
                </div>
            `;
        };

        // Estimate max pressure drop based on required pressure + atmospheric
        const maxPressureDropPSI = (summaryData.preq_psi + summaryData.patm_psi) * 0.8; // Example: 80% of total available pressure
        // Estimate max velocity based on initial velocity (e.g., allow for 50% increase)
        const maxVelocityMS = summaryData.vin_ms * 1.5;


        return `
            <div>
                <h3 class="text-xl font-semibold mb-4">${T.summary_title}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    ${createGauge(pressureDrop.dP_psi_total, maxPressureDropPSI, `PSI (${APP_UTILS.format(pressureDrop.dP_bar_total, 3)} bar)`, T.summary_total_pressure_drop)}
                    ${createGauge(pressureDrop.final_Vout_ms, maxVelocityMS, `m/s (${APP_UTILS.format(pressureDrop.final_Vout_fts, 2)} ft/s)`, T.summary_final_velocity)}
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
                    ${createInfoCard(T.summary_material, summaryData.solids_material)}
                    ${createInfoCard(T.summary_mass_flow, `${APP_UTILS.format(summaryData.ms_tph, 2)} tph`)}
                    ${createInfoCard(T.summary_air_flow_scfm, APP_UTILS.format(summaryData.q_stf_scfm, 1))}
                    ${createInfoCard(T.summary_air_flow_sm3h, APP_UTILS.format(summaryData.q_std_m3h, 1))}
                    ${createInfoCard(T.summary_initial_velocity, `${APP_UTILS.format(summaryData.vin_ms, 2)} m/s`, `(${APP_UTILS.format(summaryData.vin_fts, 2)} ft/s)`)}
                    ${createInfoCard(T.summary_final_temperature, `${APP_UTILS.format(summaryData.final_temp_c, 1)} °C`, `(${APP_UTILS.format(summaryData.final_temp_f, 1)} °F)`)}
                    ${createInfoCard(T.summary_total_length, `${APP_UTILS.format(segmentation.total_m, 2)} m`, `(${APP_UTILS.format(segmentation.total_ft, 2)} ft)`)}
                </div>
            </div>
        `;
    },

    renderSegmentationTable(segmentation) {
        const rows = segmentation.sections.map(s => `
            <tr>
                <td>${s.section_number}</td>
                <td>${s.component}</td>
                <td>${s.orientation}</td>
                <td>${APP_UTILS.format(s.EQ_Length_ft, 2)}</td>
                <td>${APP_UTILS.format(s.EQ_Cumulative_ft, 2)}</td>
            </tr>
        `).join('');
         return `
            <div>
                <h3 class="text-xl font-semibold mb-2">${T.segmentation_table_title}</h3>
                <div class="overflow-x-auto rounded-lg border max-h-96">
                    <table>
                        <thead class="sticky top-0 bg-gray-100">
                            <tr>
                                <th>${T.th_section}</th>
                                <th>${T.th_component}</th>
                                <th>${T.th_orientation}</th>
                                <th>${T.th_eq_length_ft}</th>
                                <th>${T.th_cumulative_ft}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `;
    },

    renderPressureDropTable(sectionsData) {
        const headers = [
           '#', T.th_orient, 'L (ft)', 'D (ft)', 'f', 'W', 'Fr', 'K', 'R', 'Vp (ft/s)', 'dZ (ft)',
           'ρ_in', 'ρ_out', 'P_in', 'P_out', 'V_in', 'V_out',
           'ΔP_gas', 'ΔP_sol_acc', 'ΔP_sol_flow', 'ΔP_gas_elv', 'ΔP_sol_elv', 'ΔP_misc', 'ΔP_total'
       ];

       const rows = sectionsData.map(s => `
           <tr>
                <td>${s.section_number}</td>
                <td>${s.orientation.substring(0,4)}</td>
                <td>${APP_UTILS.format(s.L_ft, 2)}</td>
                <td>${APP_UTILS.format(s.D_ft, 3)}</td>
                <td>${APP_UTILS.format(s.f, 4)}</td>
                <td>${APP_UTILS.format(s.W, 2)}</td>
                <td>${APP_UTILS.format(s.Fr, 2)}</td>
                <td>${APP_UTILS.format(s.K, 2)}</td>
                <td>${APP_UTILS.format(s.R, 2)}</td>
                <td>${APP_UTILS.format(s.Vp_fts, 2)}</td>
                <td>${APP_UTILS.format(s.dZ_ft, 2)}</td>
                <td>${APP_UTILS.format(s.roin_gas, 4)}</td>
                <td>${APP_UTILS.format(s.roout_gas, 4)}</td>
                <td>${APP_UTILS.format(s.Pin_psia, 2)}</td>
                <td>${APP_UTILS.format(s.Pout_psia, 2)}</td>
                <td>${APP_UTILS.format(s.Vin_fts, 2)}</td>
                <td>${APP_UTILS.format(s.Vout_fts, 2)}</td>
                <td>${APP_UTILS.format(s.Pdrop_flowgas, 4)}</td>
                <td>${APP_UTILS.format(s.Pdrop_solidacc, 4)}</td>
                <td>${APP_UTILS.format(s.Pdrop_flowsol, 4)}</td>
                <td>${APP_UTILS.format(s.Pdrop_elvgas, 4)}</td>
                <td>${APP_UTILS.format(s.Pdrop_elvsol, 4)}</td>
                <td>${APP_UTILS.format(s.Pdrop_misc, 4)}</td>
                <td class="font-bold">${APP_UTILS.format(s.Pdrop_psi, 4)}</td>
           </tr>
       `).join('');

       return `
           <div>
               <h3 class="text-xl font-semibold mb-2">${T.pressure_drop_table_title}</h3>
               <div class="overflow-x-auto rounded-lg border max-h-[40rem]">
                   <table>
                       <thead class="sticky top-0 bg-gray-100">
                           <tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>
                       </thead>
                       <tbody>${rows}</tbody>
                   </table>
               </div>
           </div>
       `;
   },

    displayError(message) {
        document.getElementById('error-container').innerHTML = `<div class="error-panel"><strong>${T.error_prefix}:</strong> ${message}</div>`;
        document.getElementById('message-container').innerHTML = ''; // Limpiar éxito
    },
    
    displaySuccess(message) {
        document.getElementById('message-container').innerHTML = `<div class="success-panel"><strong>${message}</strong></div>`;
        document.getElementById('error-container').innerHTML = ''; // Limpiar error
    },

    clearMessages() {
        document.getElementById('error-container').innerHTML = '';
        document.getElementById('message-container').innerHTML = '';
    }
};


// --- MVC: CONTROLLER (Event Listeners and App Logic) ---

const app = {
    _results: null,
    // _library: { materials: [], pipe_types: [] }, // Eliminado

    init() {
        // Form controls
        document.getElementById('calc-form').addEventListener('submit', this.handleCalculate.bind(this));
        document.getElementById('clear-form-btn').addEventListener('click', this.clearForm.bind(this));
        document.getElementById('add-segment-btn').addEventListener('click', () => addSegment());
        document.getElementById('fill-example-btn').addEventListener('click', this.fillExample.bind(this));

        // Export buttons
        document.getElementById('export-csv-btn').addEventListener('click', () => this.exportData('csv'));
        document.getElementById('export-xlsx-btn').addEventListener('click', () => this.exportData('xlsx'));
        document.getElementById('export-pdf-btn').addEventListener('click', () => this.exportData('pdf'));
        
        // Botones de Guardar eliminados
        
        // Solver buttons
        document.getElementById('suggest-params-btn').addEventListener('click', this.handleSuggestParameters.bind(this));
        document.getElementById('cancel-solver-btn').addEventListener('click', this.hideSolverModal.bind(this));
        document.getElementById('solver-results-tbody').addEventListener('click', this.handleApplySuggestion.bind(this));


        // Initial segments
        addSegment({ length: 30, orientation: 'Horizontal', accessory: '45' });
        addSegment({ length: 15, orientation: 'Vertical', accessory: 'Diverter Valve 30°' });
        addSegment({ length: 8, orientation: 'Horizontal', accessory: '' });

        // Tab navigation
        const tabNav = document.getElementById('tab-nav');
        const tabContent = document.getElementById('tab-content');
        tabNav.addEventListener('click', e => {
            if (e.target && e.target.classList.contains('tab-button')) {
                // Deactivate all
                tabNav.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                tabContent.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));

                // Activate clicked
                e.target.classList.add('active');
                const tabId = e.target.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            }
        });

        // Initialize dynamic labels for segments
        document.querySelectorAll('.pipe-segment').forEach(updateSegmentLabels);
        
        // Carga de BD eliminada
    },

    // Funciones de BD eliminadas: loadLibraryData, onPipeTypeChange

    handleCalculate(event) {
        event.preventDefault();
        view.clearMessages();
        const inputs = this.getInputs();

        const calcButton = event.target.querySelector('button[type="submit"]');
        calcButton.disabled = true;
        calcButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${T.calculating}...`;

        fetch('index.php?action=calculate', { // Use index.php with action
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(inputs),
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(`${T.server_error_prefix}: ${response.status} ${response.statusText}. ${T.details}: ${text}`) });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this._results = data.results; // Save full results object
                view.renderResults(data.results);
                this.toggleExportButtons(true);
                // this.toggleSaveButton(true); // Eliminado
            } else {
                view.displayError(data.error);
                this.toggleExportButtons(false);
                // this.toggleSaveButton(false); // Eliminado
            }
        })
        .catch(error => {
            view.displayError(`${T.communication_error}: ${error.message}`);
            this.toggleExportButtons(false);
            // this.toggleSaveButton(false); // Eliminado
        })
        .finally(() => {
            calcButton.disabled = false;
            calcButton.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7 2a1 1 0 00-.707 1.707L8.586 6H4a1 1 0 000 2h4.586l-2.293 2.293a1 1 0 101.414 1.414l4-4a1 1 0 000-1.414l-4-4A1 1 0 007 2zM12 10a1 1 0 011-1h4a1 1 0 110 2h-4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                ${T.calculate}`;
        });
    },
    
    handleSuggestParameters(event) {
        event.preventDefault();
        view.clearMessages();
        
        // Get all inputs, the backend will ignore D_in, Vin_ms, Preq_bar
        const inputs = this.getInputs(); 

        this.showSolverModal(true); // Show loading state

        fetch('index.php?action=suggest_parameters', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(inputs),
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(`${T.server_error_prefix}: ${response.status} ${response.statusText}. ${T.details}: ${text}`) });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.report && data.report.length > 0) {
                    this.renderSolverResults(data.report);
                } else {
                    this.showSolverModal(false, true); // Show "no solutions"
                }
            } else {
                view.displayError(data.error); // Show error in main panel
                this.hideSolverModal();
            }
        })
        .catch(error => {
            view.displayError(`${T.communication_error}: ${error.message}`);
            this.hideSolverModal();
        });
    },

    renderSolverResults(report) {
        const tbody = document.getElementById('solver-results-tbody');
        tbody.innerHTML = ''; // Clear previous results
        
        report.forEach(item => {
            const row = document.createElement('tr');
            let cells = '';
            if (item.status === 'Success') {
                row.className = 'solver-table-row-success hover:bg-green-200';
                cells = `
                    <td class="p-3 font-medium">${item.D_in} in</td>
                    <td class="p-3"><span class="font-medium text-green-700">${T.solver_status_success}</span></td>
                    <td class="p-3">${APP_UTILS.format(item.solution.Vin_ms, 2)} m/s</td>
                    <td class="p-3">${APP_UTILS.format(item.solution.Vout_ms, 2)} m/s</td>
                    <td class="p-3">${APP_UTILS.format(item.solution.R_loading, 2)}</td>
                    <td class="p-3 font-medium">${APP_UTILS.format(item.solution.Preq_bar, 3)} bar</td>
                    <td class="p-3 text-right">
                        <button class="btn btn-primary !py-1 !px-3 apply-solution-btn"
                            data-d_in="${item.solution.D_in}"
                            data-vin_ms="${item.solution.Vin_ms}"
                            data-preq_bar="${item.solution.Preq_bar}">
                            ${T.solver_apply}
                        </button>
                    </td>
                `;
            } else { // Failure
                row.className = 'solver-table-row-fail';
                cells = `
                    <td class="p-3 font-medium">${item.D_in} in</td>
                    <td class="p-3"><span class="font-medium text-red-700">${T.solver_status_fail}</span></td>
                    <td class="p-3 text-red-700" colspan="4">${item.reason}</td>
                    <td class="p-3"></td>
                `;
            }
            row.innerHTML = cells;
            tbody.appendChild(row);
        });

        this.showSolverModal(false, false); // Show results
    },
    
    handleApplySuggestion(event) {
        if (!event.target.classList.contains('apply-solution-btn')) {
            return; // Click was not on an "Apply" button
        }
        event.preventDefault();
        
        const button = event.target;
        
        // Fill Pipe Line tab
        document.getElementById('d_in').value = button.dataset.d_in;
        // Trigger change event for pipe material to auto-fill roughness (if it exists)
        // document.getElementById('pipe_material').dispatchEvent(new Event('change')); // No longer needed
        
        // Fill Flow Conditions tab
        document.getElementById('vin_ms').value = button.dataset.vin_ms;
        document.getElementById('preq_bar').value = button.dataset.preq_bar;
        
        this.hideSolverModal();
        
        // Optional: Switch to the tab to show the user the fields are filled
        document.querySelector('.tab-button[data-tab="tab-2"]').click();
    },

    showSolverModal(isLoading = true, noSolutions = false) {
        document.getElementById('solver-loading').style.display = isLoading ? 'block' : 'none';
        document.getElementById('solver-results').style.display = (!isLoading && !noSolutions) ? 'block' : 'none';
        document.getElementById('solver-no-solutions').style.display = noSolutions ? 'block' : 'none';
        document.getElementById('solver-modal-overlay').classList.add('active');
    },

    hideSolverModal() {
        document.getElementById('solver-modal-overlay').classList.remove('active');
    },

    getInputs() {
        const inputs = {
            atmospheric: {
                Location: document.getElementById('location').value,
                Height_m: parseFloat(document.getElementById('height_m').value),
                Humidity_pct: parseFloat(document.getElementById('humidity_pct').value),
                Tamb_C: parseFloat(document.getElementById('tamb_c').value),
            },
            gas: {
                Moisture_air: parseFloat(document.getElementById('moisture_air').value),
            },
            material: {
                solids_material: document.getElementById('solids_material').value,
                ms_tph: parseFloat(document.getElementById('ms_tph').value),
                T_solids_C: parseFloat(document.getElementById('t_solids_c').value),
            },
            pipe: {
                pipe_material: document.getElementById('pipe_material').value,
                pipe_roughness: parseFloat(document.getElementById('pipe_roughness').value),
                D_in: parseFloat(document.getElementById('d_in').value),
                oversizing_param: parseFloat(document.getElementById('oversizing_param').value),
            },
            flow: {
                Vin_ms: parseFloat(document.getElementById('vin_ms').value),
                Tin_gas_C: parseFloat(document.getElementById('tin_gas_c').value),
                Preq_bar: parseFloat(document.getElementById('preq_bar').value),
            },
            segments: []
        };

        document.querySelectorAll('.pipe-segment').forEach(seg => {
            inputs.segments.push({
                length: parseFloat(seg.querySelector('.segment-length').value),
                orientation: seg.querySelector('.segment-orientation').value,
                accessory: seg.querySelector('.segment-accessory').value,
            });
        });
        return inputs;
    },

    clearForm() {
        document.getElementById('calc-form').reset();
        document.getElementById('pipe-segments-container').innerHTML = '';
        addSegment();
        document.getElementById('results-container').innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="mt-2 font-medium">${T.results_placeholder_title}</p>
                <p class="text-sm">${T.results_placeholder_subtitle}</p>
            </div>`;
        view.clearMessages();
        this.toggleExportButtons(false);
        // this.toggleSaveButton(false); // Eliminado
    },

    fillExample() {
        this.clearForm();
        document.getElementById('location').value = "Balcones";
        document.getElementById('height_m').value = 202.08;
        document.getElementById('humidity_pct').value = 77;
        document.getElementById('tamb_c').value = 20;
        document.getElementById('moisture_air').value = 0.033;
        
        document.getElementById('solids_material').value = "Cemento"; 
        document.getElementById('t_solids_c').value = 100;
        document.getElementById('pipe_material').value = "Steel, schedule 40 pipe, internally score"; 
        document.getElementById('pipe_roughness').value = 0.0005;
        
        document.getElementById('ms_tph').value = 72.56;
        document.getElementById('d_in').value = 12;
        document.getElementById('vin_ms').value = 9.25;
        document.getElementById('tin_gas_c').value = 180;
        document.getElementById('preq_bar').value = 2.5;
        document.getElementById('oversizing_param').value = 4; 

        const segmentsContainer = document.getElementById('pipe-segments-container');
        segmentsContainer.innerHTML = '';

        const exampleSegments = [
            { length: 3.903, orientation: 'Horizontal', accessory: '90' },
            { length: 41.537, orientation: 'Horizontal', accessory: '90' },
            { length: 7.824, orientation: 'Vertical', accessory: '90' },
            { length: 137.28, orientation: 'Horizontal', accessory: '60' },
            { length: 26.400, orientation: 'Horizontal', accessory: '60' },
            { length: 53.760, orientation: 'Horizontal', accessory: '90' },
            { length: 7.632, orientation: 'Vertical', accessory: '90' },
            { length: 60.480, orientation: 'Horizontal', accessory: '60' },
            { length: 23.520, orientation: 'Horizontal', accessory: '90' },
            { length: 44.400, orientation: 'Vertical', accessory: '90' },
            { length: 3.085, orientation: 'Horizontal', accessory: 'Diverter Valve 30°' },
            { length: 3.346, orientation: 'Horizontal', accessory: 'Diverter Valve 30°' },
            { length: 4.320, orientation: 'Horizontal', accessory: '90' },
            { length: 20.793, orientation: 'Horizontal', accessory: 'Diverter Valve 30°' },
            { length: 4.166, orientation: 'Horizontal', accessory: '90' },
            { length: 6.163, orientation: 'Horizontal', accessory: '90' },
            { length: 3.080, orientation: 'Vertical', accessory: '' },
        ];

        exampleSegments.forEach(s => addSegment(s));
    },

    toggleExportButtons(enabled) {
        document.getElementById('export-csv-btn').disabled = !enabled;
        document.getElementById('export-xlsx-btn').disabled = !enabled;
        document.getElementById('export-pdf-btn').disabled = !enabled;
    },
    
    // toggleSaveButton(enabled) { ... } // Eliminado
    
    // Funciones de modal de guardado eliminadas
    // showSaveModal() { ... }
    // hideSaveModal() { ... }
    // handleSaveCalculation() { ... }

    exportData(format) {
        if (!this._results) return;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const inputs = this._results.inputs;
        const sections = this._results.segmentation.sections;
        const pressureDrops = this._results.pressureDrop.sectionsData;

        const sectionsHeaders = [T.th_section, T.th_component, T.th_orientation, T.th_eq_length_ft, T.th_cumulative_ft];
        const sectionsData = sections.map(s => [s.section_number, s.component, s.orientation, APP_UTILS.format(s.EQ_Length_ft, 2), APP_UTILS.format(s.EQ_Cumulative_ft, 2)]);

        const pressureDropsHeaders = ['#', T.th_orient, 'L (ft)', 'P_in', 'P_out', 'V_in', 'V_out', 'ρ_in', 'ΔP_total'];
        const pressureDropsData = pressureDrops.map(s => [
            s.section_number, s.orientation.substring(0,4), APP_UTILS.format(s.L_ft, 2), APP_UTILS.format(s.Pin_psia, 2), APP_UTILS.format(s.Pout_psia, 2), APP_UTILS.format(s.Vin_fts, 2), APP_UTILS.format(s.Vout_fts, 2), APP_UTILS.format(s.roin_gas, 4), APP_UTILS.format(s.Pdrop_psi, 4)
        ]);

        if (format === 'csv') {
            const csvContentSections = [sectionsHeaders.join(","), ...sectionsData.map(e => e.join(","))].join("\n");
            this.downloadFile(csvContentSections, "Pneumatic_Calculation_Report_Sections.csv", "text/csv;charset=utf-8;");

            const csvContentDrops = [pressureDropsHeaders.join(","), ...pressureDropsData.map(e => e.join(","))].join("\n");
            this.downloadFile(csvContentDrops, "Pneumatic_Calculation_Report_PressureDrops.csv", "text/csv;charset=utf-8;");
        }
        else if (format === 'xlsx') {
            const wb = XLSX.utils.book_new();
            const wsSections = XLSX.utils.aoa_to_sheet([sectionsHeaders, ...sectionsData]);
            XLSX.utils.book_append_sheet(wb, wsSections, "Sections");

            const wsDrops = XLSX.utils.aoa_to_sheet([pressureDropsHeaders, ...pressureDropsData]);
            XLSX.utils.book_append_sheet(wb, wsDrops, "PressureDrops");

            XLSX.writeFile(wb, "Pneumacalc_Report.xlsx");
        }
        else if (format === 'pdf') {
            doc.setFontSize(18);
            doc.text(T.pdf_report_title, 14, 22);
            doc.setFontSize(11);
            doc.text(`${T.location}: ${inputs.atmospheric.Location}`, 14, 32);
            doc.text(`${T.pdf_date}: ${new Date().toLocaleDateString()}`, 14, 38);

            doc.autoTable({
                startY: 50,
                head: [sectionsHeaders],
                body: sectionsData,
                headStyles: { fillColor: [22, 160, 133] },
                didDrawPage: (data) => {
                    doc.setFontSize(16);
                    doc.text(T.segmentation_table_title, 14, data.settings.margin.top - 5);
                }
            });

            doc.addPage();
            doc.autoTable({
                startY: 30,
                head: [pressureDropsHeaders],
                body: pressureDropsData,
                headStyles: { fillColor: [22, 160, 133] },
                 didDrawPage: (data) => {
                    doc.setFontSize(16);
                    doc.text(T.pressure_drop_table_title_short, 14, data.settings.margin.top - 5);
                }
            });

            doc.save("Pneumacalc_Report.pdf");
        }
    },

    downloadFile(content, fileName, mimeType) {
        const a = document.createElement('a');
        const blob = new Blob([content], {type: mimeType});
        const url = URL.createObjectURL(blob);
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }, 0);
    }
};

function addSegment(data = { length: '', orientation: 'Horizontal', accessory: '' }) {
    const container = document.getElementById('pipe-segments-container');
    const segmentDiv = document.createElement('div');
    segmentDiv.className = 'pipe-segment grid grid-cols-1 sm:grid-cols-7 gap-3 items-end p-3 border rounded-md bg-gray-50';

    segmentDiv.innerHTML = `
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 segment-label-length">${T.length_m}</label>
            <input type="number" min="0" step="any" class="segment-length form-input mt-1 block w-full rounded-md" value="${data.length}" required>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 segment-label-orientation">${T.orientation}</label>
            <select class="segment-orientation form-input mt-1 block w-full rounded-md">
                <option value="Horizontal" ${data.orientation === 'Horizontal' ? 'selected' : ''}>${T.horizontal}</option>
                <option value="Vertical" ${data.orientation === 'Vertical' ? 'selected' : ''}>${T.vertical}</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 segment-label-accessory">${T.accessory_angle}</label>
            <input type="text" list="accessory-list" class="segment-accessory form-input mt-1 block w-full rounded-md" value="${data.accessory}">
        </div>
        <button type="button" class="remove-segment-btn btn bg-red-500 hover:bg-red-600 text-white p-2 h-10 w-10 flex items-center justify-center rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
        </button>
    `;
    container.appendChild(segmentDiv);

    segmentDiv.querySelector('.remove-segment-btn').addEventListener('click', () => {
        segmentDiv.remove();
    });
}

function updateSegmentLabels(segmentDiv) {
    segmentDiv.querySelector('.segment-label-length').textContent = T.length_m;
    segmentDiv.querySelector('.segment-label-orientation').textContent = T.orientation;
    segmentDiv.querySelector('.segment-label-accessory').textContent = T.accessory_angle;
    const select = segmentDiv.querySelector('.segment-orientation');
    select.querySelector('option[value="Horizontal"]').textContent = T.horizontal;
    select.querySelector('option[value="Vertical"]').textContent = T.vertical;
}


document.addEventListener('DOMContentLoaded', () => {
     app.init();
});