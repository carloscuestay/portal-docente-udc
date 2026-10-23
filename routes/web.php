<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialFileController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

// Instalación desde el navegador: solo funciona si INSTALL_TOKEN (>= 24 caracteres) está en el .env
Route::get('/instalar/{token}', function (string $token) {
    $expected = (string) config('portal.install_token');
    abort_unless(strlen($expected) >= 24 && hash_equals($expected, $token), 404);
    \Illuminate\Support\Facades\Artisan::call('portal:install');

    return response(\Illuminate\Support\Facades\Artisan::output(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->middleware('throttle:5,1');

// Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.password');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/solicitar-acceso', [Student\AccessRequestController::class, 'create'])->name('access.create');
    Route::post('/solicitar-acceso', [Student\AccessRequestController::class, 'store'])->middleware('throttle:10,1')->name('access.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/inicio', DashboardController::class)->name('dashboard');
    Route::get('/materiales/{material}/descargar', MaterialFileController::class)->name('materials.download');

    // ---------- Docente ----------
    Route::middleware('teacher')->prefix('docente')->name('teacher.')->group(function () {
        Route::resource('asignaturas', Teacher\SubjectController::class)
            ->parameters(['asignaturas' => 'subject'])->names('subjects');

        // Listas de estudiantes
        Route::get('asignaturas/{subject}/estudiantes', [Teacher\EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('asignaturas/{subject}/estudiantes/importar', [Teacher\EnrollmentController::class, 'import'])->name('enrollments.import');
        Route::get('asignaturas/{subject}/estudiantes/plantilla', [Teacher\EnrollmentController::class, 'template'])->name('enrollments.template');
        Route::patch('asignaturas/{subject}/estudiantes/{enrollment}', [Teacher\EnrollmentController::class, 'toggle'])->name('enrollments.toggle');
        Route::delete('asignaturas/{subject}/estudiantes/{enrollment}', [Teacher\EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        Route::post('solicitudes/{accessRequest}/aprobar', [Teacher\EnrollmentController::class, 'approve'])->name('requests.approve');
        Route::post('solicitudes/{accessRequest}/rechazar', [Teacher\EnrollmentController::class, 'reject'])->name('requests.reject');

        // Material
        Route::get('asignaturas/{subject}/material/crear', [Teacher\MaterialController::class, 'create'])->name('materials.create');
        Route::post('asignaturas/{subject}/material', [Teacher\MaterialController::class, 'store'])->name('materials.store');
        Route::get('material/{material}/editar', [Teacher\MaterialController::class, 'edit'])->name('materials.edit');
        Route::put('material/{material}', [Teacher\MaterialController::class, 'update'])->name('materials.update');
        Route::delete('material/{material}', [Teacher\MaterialController::class, 'destroy'])->name('materials.destroy');

        // Evaluaciones
        Route::get('asignaturas/{subject}/evaluaciones/crear', [Teacher\QuizController::class, 'create'])->name('quizzes.create');
        Route::post('asignaturas/{subject}/evaluaciones', [Teacher\QuizController::class, 'store'])->name('quizzes.store');
        Route::get('evaluaciones/{quiz}', [Teacher\QuizController::class, 'show'])->name('quizzes.show');
        Route::get('evaluaciones/{quiz}/editar', [Teacher\QuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('evaluaciones/{quiz}', [Teacher\QuizController::class, 'update'])->name('quizzes.update');
        Route::delete('evaluaciones/{quiz}', [Teacher\QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('evaluaciones/{quiz}/duplicar', [Teacher\QuizController::class, 'duplicate'])->name('quizzes.duplicate');
        Route::get('evaluaciones/{quiz}/vista-previa', [Teacher\QuizController::class, 'preview'])->name('quizzes.preview');
        Route::get('evaluaciones/{quiz}/resultados', [Teacher\GradingController::class, 'results'])->name('quizzes.results');
        Route::get('evaluaciones/{quiz}/resultados.csv', [Teacher\GradingController::class, 'export'])->name('quizzes.export');
        Route::get('intentos/{attempt}', [Teacher\GradingController::class, 'attempt'])->name('attempts.show');
        Route::put('intentos/{attempt}', [Teacher\GradingController::class, 'grade'])->name('attempts.grade');
        Route::delete('intentos/{attempt}', [Teacher\GradingController::class, 'destroy'])->name('attempts.destroy');

        // Preguntas
        Route::get('evaluaciones/{quiz}/preguntas/crear', [Teacher\QuestionController::class, 'create'])->name('questions.create');
        Route::post('evaluaciones/{quiz}/preguntas', [Teacher\QuestionController::class, 'store'])->name('questions.store');
        Route::get('preguntas/{question}/editar', [Teacher\QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('preguntas/{question}', [Teacher\QuestionController::class, 'update'])->name('questions.update');
        Route::delete('preguntas/{question}', [Teacher\QuestionController::class, 'destroy'])->name('questions.destroy');
        Route::post('preguntas/{question}/mover/{direction}', [Teacher\QuestionController::class, 'move'])
            ->whereIn('direction', ['arriba', 'abajo'])->name('questions.move');
    });

    // ---------- Estudiante ----------
    Route::middleware('student')->prefix('estudiante')->name('student.')->group(function () {
        Route::get('asignaturas/{subject}', [Student\SubjectController::class, 'show'])->name('subjects.show');
        Route::get('evaluaciones/{quiz}', [Student\QuizController::class, 'show'])->name('quizzes.show');
        Route::post('evaluaciones/{quiz}/iniciar', [Student\QuizController::class, 'start'])->name('quizzes.start');
        Route::get('intentos/{attempt}', [Student\QuizController::class, 'take'])->name('attempts.take');
        Route::post('intentos/{attempt}/guardar', [Student\QuizController::class, 'save'])->middleware('throttle:60,1')->name('attempts.save');
        Route::post('intentos/{attempt}/enviar', [Student\QuizController::class, 'submit'])->name('attempts.submit');
        Route::get('intentos/{attempt}/resultado', [Student\QuizController::class, 'result'])->name('attempts.result');
    });
});
