<?php

return [
    // Dominio de correo institucional de los estudiantes
    'institutional_domain' => env('INSTITUTIONAL_DOMAIN', 'unicartagena.edu.co'),

    // Correos (separados por coma) que entran con rol docente vía Google
    'teacher_emails' => array_values(array_filter(array_map(
        fn ($e) => strtolower(trim($e)),
        explode(',', (string) env('TEACHER_EMAILS', ''))
    ))),

    // Permite que un correo docente fuera del dominio (p. ej. gmail) use Google
    'allow_external_teacher_login' => (bool) env('ALLOW_EXTERNAL_TEACHER_LOGIN', false),

    // Tamaño máximo de archivos de material (KB)
    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 20480),

    // Token temporal para ejecutar la instalación desde el navegador (hosting sin SSH).
    // Déjelo vacío después de instalar.
    'install_token' => env('INSTALL_TOKEN'),

    'institution' => env('INSTITUTION_NAME', 'Universidad de Cartagena'),
];
