<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'required_with' => 'Complete :attribute cuando indique :values.',
    'string' => ':attribute debe ser texto.', 'integer' => ':attribute debe ser un número entero.', 'numeric' => ':attribute debe ser numérico.',
    'email' => 'Ingrese un correo válido.', 'exists' => 'El valor seleccionado para :attribute no está disponible.', 'unique' => 'Ya existe un registro con ese :attribute.',
    'in' => 'El valor de :attribute no es válido.', 'boolean' => ':attribute debe indicar sí o no.', 'prohibited' => 'No tiene permiso para asignar :attribute.',
    'date_format' => 'La fecha de :attribute no tiene el formato requerido.', 'after_or_equal' => ':attribute debe ser igual o posterior a :date.',
    'image' => 'El archivo :attribute debe ser una imagen.', 'mimes' => 'El archivo :attribute debe ser de tipo :values.', 'file' => 'Seleccione un archivo válido en :attribute.',
    'dimensions' => 'Las dimensiones de :attribute superan el límite permitido.',
    'min' => ['string' => ':attribute debe tener al menos :min caracteres.', 'numeric' => ':attribute debe ser al menos :min.', 'array' => 'Seleccione al menos :min elementos en :attribute.'],
    'max' => ['string' => ':attribute no debe superar :max caracteres.', 'numeric' => ':attribute no debe superar :max.', 'file' => ':attribute no debe superar :max KB.', 'array' => 'Seleccione como máximo :max elementos en :attribute.'],
    'between' => ['numeric' => ':attribute debe estar entre :min y :max.'],
    'attributes' => ['title' => 'título', 'description' => 'descripción', 'detected_at' => 'detección', 'address' => 'dirección', 'latitude' => 'latitud', 'longitude' => 'longitud',
        'incident_type_id' => 'tipo de incidencia', 'zone_id' => 'zona', 'reported_by_label' => 'persona o área que reporta', 'priority' => 'prioridad', 'priority_reason' => 'motivo de prioridad', 'photos' => 'fotografías', 'photos.*' => 'fotografía', 'status_code' => 'estado', 'note' => 'observación', 'lock_version' => 'versión del expediente', 'attention_summary' => 'justificación de atención', 'assigned_to' => 'responsable', 'verify_location' => 'verificación de ubicación'],
];
