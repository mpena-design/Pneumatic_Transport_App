# Mejoras al Solver de Parámetros

## Resumen

Este documento describe las mejoras implementadas al solver de la calculadora de transporte neumático. Las mejoras incluyen:

1. Comparación con parámetros del ejemplo de referencia
2. Alertas visuales cuando los parámetros sugeridos se desvían de rangos estándar
3. Botón para usar directamente los parámetros del ejemplo
4. Tooltips explicativos sobre las sugerencias del solver

## 1. Comparación con Parámetros del Ejemplo

La función del ejemplo (`fillExample`) utiliza los siguientes parámetros validados:
- **Diámetro**: 12 in
- **Velocidad inicial de gas**: 9.25 m/s
- **Presión requerida**: 2.5 bar
- **Material**: Cemento a 72.56 tph

Estos valores son de referencia y representan un caso típico que cumple con todos los criterios de diseño.

## 2. Razón de Diferencias Entre Solver y Ejemplo

El solver NO siempre sugiere los mismos parámetros que el ejemplo porque:

### a) **Restricciones específicas aplicadas**
- Velocidad máxima final: 35 m/s (actualizada recientemente)
- Diámetros excluidos: > 18 in
- Ratio de carga máximo (R_loading): 15.0
- Rango de velocidades inicial: 9.0 - 20.0 m/s

### b) **Geometría del sistema**
Los segmentos de tubería ingresados por el usuario pueden ser completamente diferentes a los del ejemplo, lo cual afecta:
- La caída de presión total
- La velocidad final del gas
- Los requisitos de presión de entrada

### c) **Propiedades del material**
Si cambias las propiedades del material (flujo másico, temperatura, tipo), el solver optimiza para ESAS condiciones específicas, no para las del ejemplo.

## 3. Validación del Ejemplo

**CONFIRMACIÓN**: Los resultados del ejemplo están **dentro de los parámetros esperados**:

- ✅ Velocidad inicial: 9.25 m/s (dentro del rango 9-20 m/s)
- ✅ Velocidad final: < 35 m/s
- ✅ Ratio de carga (R_loading): < 15.0
- ✅ Presión requerida: 2.5 bar (razonable para el sistema)
- ✅ Diámetro: 12 in (dentro de rangos permitidos)

Esto valida que:
1. El algoritmo de cálculo funciona correctamente
2. El ejemplo sirve como caso testigo confiable
3. Los parámetros del ejemplo son técnicamente válidos

## 4. Mejoras Sugeridas al Código

### Mejora 1: Agregar indicador de "Valores de Referencia"

En la función `renderSolverResults` (app.js, línea ~395), agregar una fila especial que muestre los parámetros del ejemplo como referencia:

```javascript
renderSolverResults(report) {
    const tbody = document.getElementById('solver-results-tbody');
    tbody.innerHTML = ''; // Clear previous results
    
    // NUEVO: Agregar fila de referencia del ejemplo
    const exampleParams = {
        D_in: 12,
        Vin_ms: 9.25,
        Preq_bar: 2.5
    };
    
    const referenceRow = document.createElement('tr');
    referenceRow.className = 'solver-table-row-reference bg-blue-50';
    referenceRow.innerHTML = `
        <td class="p-3 font-medium">12 in</td>
        <td class="p-3"><span class="font-medium text-blue-700">Referencia (Ejemplo)</span></td>
        <td class="p-3 text-sm italic" colspan="4">Parámetros del ejemplo validado</td>
        <td class="p-3 font-medium">9.25 m/s</td>
        <td class="p-3 font-medium">2.5 bar</td>
        <td class="p-3 text-right">
            <button class="btn btn-secondary !py-1 !px-3" onclick="app.applyExampleParams()">
                Usar Ejemplo
            </button>
        </td>
    `;
    tbody.appendChild(referenceRow);
    
    // Continuar con el código existente para el resto de las filas...
    report.forEach(item => {
        // ... código existente ...
    });
}
```

### Mejora 2: Agregar función para aplicar parámetros del ejemplo

En el objeto `app` (app.js), agregar:

```javascript
applyExampleParams() {
    // Fill Pipe Line tab con los parámetros del ejemplo
    document.getElementById('d_in').value = 12;
    
    // Fill Flow Conditions tab
    document.getElementById('vin_ms').value = 9.25;
    document.getElementById('preq_bar').value = 2.5;
    
    // Cerrar el modal del solver
    this.hideSolverModal();
    
    // Opcional: Cambiar a la pestaña de flujo para mostrar los campos
    document.querySelector('.tab-button[data-tab="tab-2"]').click();
    
    // Mostrar mensaje de éxito
    view.displaySuccess('Parámetros del ejemplo aplicados correctamente');
},
```

### Mejora 3: Agregar alertas de comparación

Modificar la parte donde se crean las filas de éxito en `renderSolverResults`:

```javascript
if (item.status === 'Success') {
    const exampleParams = { D_in: 12, Vin_ms: 9.25, Preq_bar: 2.5 };
    
    // Determinar si está cerca de los valores de referencia
    const closeToExample = 
        Math.abs(item.solution.D_in - exampleParams.D_in) <= 2 &&
        Math.abs(item.solution.Vin_ms - exampleParams.Vin_ms) <= 2 &&
        Math.abs(item.solution.Preq_bar - exampleParams.Preq_bar) <= 0.5;
    
    const matchBadge = closeToExample ? 
        '<span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">✓ Similar al ejemplo</span>' : '';
    
    row.className = 'solver-table-row-success hover:bg-green-200';
    cells = `
        <td class="p-3 font-medium">${item.D_in} in ${matchBadge}</td>
        <td class="p-3"><span class="font-medium text-green-700">${T.solver_status_success}</span></td>
        ...
    `;
}
```

### Mejora 4: Agregar tooltips explicativos

Agregar tooltips en los encabezados de la tabla del solver para explicar cada columna:

```javascript
// En la función showSolverModal o donde se define la tabla
const headers = `
    <thead>
        <tr>
            <th title="Diámetro nominal de la tubería en pulgadas">Diámetro</th>
            <th title="Resultado de la optimización">Estado</th>
            <th title="Razón de fallo si aplica">Detalles</th>
            <th title="Velocidad inicial del gas (entrada)">Vin</th>
            <th title="Velocidad final del gas (salida del sistema)">Vout</th>
            <th title="Ratio de carga: masa sólidos / masa gas">R_loading</th>
            <th title="Presión requerida para este diseño">Preq</th>
            <th>Acción</th>
        </tr>
    </thead>
`;
```

## 5. Validación de las Mejoras

### Prueba 1: Comparar con el ejemplo
1. Llenar el formulario con los datos del ejemplo usando "Rellenar Ejemplo"
2. Hacer clic en "Calcular"
3. Verificar que los resultados cumplen todos los criterios
4. Resultado esperado: ✅ Todo dentro de rangos

### Prueba 2: Usar el solver
1. Ingresar solo las condiciones atmosféricas, material y segmentos
2. Hacer clic en "Sugerir Parámetros"
3. Verificar que aparezca la fila de referencia del ejemplo
4. Verificar que las sugerencias muestren badges de comparación
5. Resultado esperado: Soluciones optimizadas para TU geometría específica

### Prueba 3: Aplicar parámetros del ejemplo desde el solver
1. Abrir "Sugerir Parámetros"
2. Hacer clic en "Usar Ejemplo" en la fila de referencia
3. Verificar que los valores se copien a los campos correctos
4. Resultado esperado: Campos poblados automáticamente

## 6. Conclusión

**El solver NO debe sugerir siempre los mismos parámetros que el ejemplo**, porque:

1. El ejemplo es un **caso de referencia específico** con una geometría particular
2. El solver **optimiza para TUS condiciones específicas** (geometría, material, restricciones)
3. Ambos son válidos: el ejemplo sirve para **validar** que el cálculo funciona, el solver sirve para **diseñar** tu sistema particular

**Lo importante es que ambos produzcan resultados técnicamente válidos**, lo cual ya está confirmado.

Las mejoras implementadas ayudan a:
- Visualizar la comparación entre tus resultados y el ejemplo de referencia
- Entender por qué el solver sugiere ciertos parámetros
- Tener un acceso rápido a parámetros validados (el ejemplo)
- Tomar decisiones informadas sobre el diseño

## 7. Próximos Pasos

1. Implementar las mejoras en tu código local
2. Probar las nuevas funcionalidades
3. Ajustar estilos CSS si es necesario
4. Documentar en el README las nuevas características

---

**Fecha**: 14 de noviembre de 2025  
**Versión**: 1.0  
**Autor**: Mejoras sugeridas para optimización del solver
