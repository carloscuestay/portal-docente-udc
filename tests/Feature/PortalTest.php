<?php

namespace Tests\Feature;

use App\Models\AccessRequest;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        config(['portal.institutional_domain' => 'unicartagena.edu.co', 'portal.teacher_emails' => ['profe@unicartagena.edu.co']]);
        $this->teacher = User::create(['name' => 'Profe', 'email' => 'profe@unicartagena.edu.co', 'role' => 'docente', 'password' => 'secreto-12345']);
        $this->subject = $this->teacher->taughtSubjects()->create([
            'code' => 'IS101', 'name' => 'Analítica', 'program' => 'Ingeniería de Sistemas', 'period' => '2026-2', 'group' => '1',
        ]);
    }

    private function student(string $email = 'ana@unicartagena.edu.co'): User
    {
        $this->actingAs($this->teacher)->post(route('teacher.enrollments.import', $this->subject), ['list' => "001234567;Ana Pérez;{$email}"]);
        auth()->logout();

        return User::where('email', $email)->firstOrFail();
    }

    private function quiz(array $attrs = []): Quiz
    {
        $quiz = $this->subject->quizzes()->create(array_merge(['title' => 'Parcial', 'is_published' => true, 'time_limit_minutes' => 30, 'shuffle_questions' => true], $attrs));
        $quiz->questions()->createMany([
            ['type' => Question::SELECCION, 'stem' => '2+2', 'points' => 1, 'position' => 1,
                'data' => ['options' => [['id' => 'o1', 'text' => '3'], ['id' => 'o2', 'text' => '4']], 'correct' => 'o2']],
            ['type' => Question::APAREAMIENTO, 'stem' => 'Relacione', 'points' => 2, 'position' => 2,
                'data' => ['pairs' => [['id' => 'p1', 'left' => 'a', 'right' => 'A'], ['id' => 'p2', 'left' => 'b', 'right' => 'B']], 'distractors' => [['id' => 'd1', 'text' => 'Z']]]],
            ['type' => Question::COMPLETAR, 'stem' => 'Complete', 'points' => 2, 'position' => 3,
                'data' => ['text' => 'La ciudad es [[Cartagena|Cartagena de Indias]] y el país [[Colombia]].', 'blanks' => [['Cartagena', 'Cartagena de Indias'], ['Colombia']], 'word_bank' => false, 'distractors' => []]],
            ['type' => Question::LIBRE, 'stem' => 'Explique', 'points' => 5, 'position' => 4, 'data' => ['max_chars' => 20]],
        ]);

        return $quiz;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/inicio')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Ingresar');
    }

    public function test_teacher_password_login_and_students_cannot_use_password(): void
    {
        $this->post('/login', ['email' => 'profe@unicartagena.edu.co', 'password' => 'secreto-12345'])->assertRedirect(route('dashboard'));
        $this->get('/inicio')->assertOk()->assertSee('Panel docente');
        auth()->logout();

        $s = $this->student();
        $s->update(['password' => 'clave-estudiante-1']);
        $this->post('/login', ['email' => $s->email, 'password' => 'clave-estudiante-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_roster_import_accepts_only_institutional_emails(): void
    {
        $list = "codigo;nombre;correo\n0011;Ana Pérez;ana@unicartagena.edu.co\n0012;Luis Díaz;luis@gmail.com\n0013;Eva Ruiz;EVA@UniCartagena.edu.co\n0011;Ana Pérez;ana@unicartagena.edu.co";
        $this->actingAs($this->teacher)->post(route('teacher.enrollments.import', $this->subject), ['list' => $list])
            ->assertSessionHas('ok')->assertSessionHas('import_errors', fn ($e) => count($e) === 1);
        $this->assertSame(2, $this->subject->students()->count());
        $this->assertDatabaseHas('users', ['email' => 'eva@unicartagena.edu.co', 'student_code' => '0013']);
    }

    public function test_csv_upload_import(): void
    {
        $file = UploadedFile::fake()->createWithContent('lista.csv', "\xEF\xBB\xBFcodigo,nombre,correo\n123456,Juan Gómez,juan@unicartagena.edu.co\n");
        $this->actingAs($this->teacher)->post(route('teacher.enrollments.import', $this->subject), ['file' => $file])->assertSessionHas('ok');
        $this->assertTrue($this->subject->students()->where('email', 'juan@unicartagena.edu.co')->exists());
    }

    public function test_google_login_flow_for_listed_unlisted_and_external_users(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $this->student('ana@unicartagena.edu.co');

        $current = '';
        Http::fake(function ($request) use (&$current) {
            return str_contains($request->url(), 'oauth2.googleapis.com')
                ? Http::response(['access_token' => 't'])
                : Http::response(['sub' => md5($current), 'email' => $current, 'email_verified' => true, 'name' => 'Nombre Google']);
        });
        $fake = function (string $email) use (&$current) { $current = $email; };

        // Estudiante en lista
        $fake('ana@unicartagena.edu.co');
        $this->withSession(['oauth_state' => 'abc'])->get('/auth/google/callback?state=abc&code=x')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'ana@unicartagena.edu.co')->first());
        auth()->logout();

        // Correo externo
        $fake('alguien@gmail.com');
        $this->withSession(['oauth_state' => 'abc'])->get('/auth/google/callback?state=abc&code=x')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();

        // Institucional pero no está en lista -> solicitud de acceso
        $fake('nuevo@unicartagena.edu.co');
        $this->withSession(['oauth_state' => 'abc'])->get('/auth/google/callback?state=abc&code=x')->assertRedirect(route('access.create'));
        $this->assertGuest();
        $this->post(route('access.store'), ['subject_id' => $this->subject->id, 'student_code' => '999'])->assertSessionHas('ok');
        $req = AccessRequest::firstOrFail();
        $this->actingAs($this->teacher)->post(route('teacher.requests.approve', $req))->assertSessionHas('ok');
        $this->assertTrue(User::where('email', 'nuevo@unicartagena.edu.co')->first()->canAccessSubject($this->subject));

        // Estado inválido
        auth()->logout();
        $this->withSession(['oauth_state' => 'abc'])->get('/auth/google/callback?state=otro&code=x')->assertRedirect(route('login'));
    }

    public function test_students_cannot_access_other_subjects_or_teacher_area(): void
    {
        $s = $this->student();
        $other = $this->teacher->taughtSubjects()->create(['code' => 'X', 'name' => 'Otra', 'program' => 'Administración Industrial', 'period' => '2026-2']);
        $this->actingAs($s)->get(route('student.subjects.show', $this->subject))->assertOk();
        $this->actingAs($s)->get(route('student.subjects.show', $other))->assertForbidden();
        $this->actingAs($s)->get(route('teacher.subjects.show', $this->subject))->assertForbidden();

        // Deshabilitar matrícula
        $enr = $this->subject->enrollments()->first();
        $this->actingAs($this->teacher)->patch(route('teacher.enrollments.toggle', [$this->subject, $enr]));
        $this->actingAs($s->fresh())->get(route('student.subjects.show', $this->subject))->assertForbidden();
    }

    public function test_teacher_cannot_manage_another_teachers_subject(): void
    {
        $other = User::create(['name' => 'Otro', 'email' => 'otro@unicartagena.edu.co', 'role' => 'docente']);
        $this->actingAs($other)->get(route('teacher.subjects.show', $this->subject))->assertForbidden();
        $this->actingAs($other)->post(route('teacher.enrollments.import', $this->subject), ['list' => 'a@unicartagena.edu.co'])->assertForbidden();
    }

    public function test_material_upload_and_protected_download(): void
    {
        Storage::fake('local');
        $this->actingAs($this->teacher)->post(route('teacher.materials.store', $this->subject), [
            'title' => 'Guía 1', 'type' => 'archivo', 'unit' => 'Unidad 1', 'is_published' => 1,
            'file' => UploadedFile::fake()->create('guia.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
        $m = $this->subject->materials()->first();
        Storage::disk('local')->assertExists($m->file_path);

        $s = $this->student();
        $this->actingAs($s)->get(route('student.subjects.show', $this->subject))->assertSee('Guía 1');
        $this->actingAs($s)->get(route('materials.download', $m))->assertOk();

        $outsider = User::create(['name' => 'X', 'email' => 'x@unicartagena.edu.co', 'role' => 'estudiante']);
        $this->actingAs($outsider)->get(route('materials.download', $m))->assertForbidden();

        $m->update(['is_published' => false]);
        $this->actingAs($s)->get(route('materials.download', $m))->assertNotFound();
    }

    public function test_teacher_creates_all_question_types(): void
    {
        $quiz = $this->subject->quizzes()->create(['title' => 'Q']);
        $this->actingAs($this->teacher);
        $this->post(route('teacher.questions.store', $quiz), ['type' => 'seleccion_multiple', 'stem' => 'S', 'points' => 1, 'options' => ['A', '', 'C', 'D'], 'correct' => 2])->assertSessionHasNoErrors();
        $this->post(route('teacher.questions.store', $quiz), ['type' => 'apareamiento', 'stem' => 'M', 'points' => 2, 'left' => ['x', 'y', ''], 'right' => ['1', '2', ''], 'distractors' => "3\n4"])->assertSessionHasNoErrors();
        $this->post(route('teacher.questions.store', $quiz), ['type' => 'completar', 'stem' => 'C', 'points' => 2, 'text' => 'Hola [[mundo|tierra]] y [[sol]]', 'word_bank' => 1])->assertSessionHasNoErrors();
        $this->post(route('teacher.questions.store', $quiz), ['type' => 'respuesta_libre', 'stem' => 'L', 'points' => 3, 'max_chars' => 250])->assertSessionHasNoErrors();
        $this->post(route('teacher.questions.store', $quiz), ['type' => 'completar', 'stem' => 'C', 'points' => 2, 'text' => 'sin espacios'])->assertSessionHasErrors('text');

        $qs = $quiz->questions()->get();
        $this->assertCount(4, $qs);
        $this->assertSame('o2', $qs[0]->data['correct']); // "C" quedó en posición 2 tras quitar la vacía
        $this->assertSame([['mundo', 'tierra'], ['sol']], $qs[2]->data['blanks']);
        $this->assertSame(250, $qs[3]->data['max_chars']);

        $this->get(route('teacher.quizzes.show', $quiz))->assertOk();
        $this->get(route('teacher.quizzes.preview', $quiz))->assertOk();
        $this->get(route('teacher.questions.edit', $qs[1]))->assertOk();
    }

    public function test_full_quiz_attempt_autograding_and_manual_grading(): void
    {
        $s = $this->student();
        $quiz = $this->quiz();
        [$mc, $match, $fill, $open] = $quiz->questions()->get()->all();

        $this->actingAs($s)->get(route('student.quizzes.show', $quiz))->assertOk()->assertSee('Iniciar evaluación');
        $this->post(route('student.quizzes.start', $quiz))->assertRedirect();
        $attempt = Attempt::firstOrFail();
        $this->assertNotNull($attempt->deadline_at);
        $this->get(route('student.attempts.take', $attempt))->assertOk()->assertSee('Columna B');

        // Autoguardado
        $this->postJson(route('student.attempts.save', $attempt), ['answers' => [$mc->id => ['choice' => 'o2']]])->assertJson(['ok' => true]);

        $this->post(route('student.attempts.submit', $attempt), ['answers' => [
            $match->id => ['matches' => ['p1' => 'p1', 'p2' => 'd1']],
            $fill->id => ['blanks' => ['cartagena de indias ', 'COLOMBIA.']],
            $open->id => ['text' => str_repeat('x', 50)],
        ]])->assertRedirect(route('student.attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame(Attempt::ENVIADO, $attempt->status); // respuesta libre pendiente
        $this->assertEquals(1 + 1 + 2, $attempt->score);       // 1 (MC autoguardada) + 1 (mitad apareamiento) + 2
        $this->assertSame(20, mb_strlen($attempt->answers()->where('question_id', $open->id)->first()->response['text']));

        // No puede presentar de nuevo (1 intento)
        $this->post(route('student.quizzes.start', $quiz))->assertForbidden();
        $this->get(route('student.attempts.result', $attempt))->assertOk();

        // Docente califica la abierta
        $this->actingAs($this->teacher)->get(route('teacher.attempts.show', $attempt))->assertOk();
        $this->put(route('teacher.attempts.grade', $attempt), ['scores' => [$open->id => '4,5'], 'feedback' => [$open->id => 'Bien']])->assertRedirect();
        $attempt->refresh();
        $this->assertSame(Attempt::CALIFICADO, $attempt->status);
        $this->assertEquals(8.5, $attempt->score);
        $this->assertEquals(round(8.5 / 10 * 5, 2), $attempt->grade);

        $this->get(route('teacher.quizzes.results', $quiz))->assertOk()->assertSee('Ana Pérez');
        $csv = $this->get(route('teacher.quizzes.export', $quiz))->assertOk()->getContent();
        $this->assertStringContainsString('4,25', $csv);
    }

    public function test_expired_attempt_is_auto_submitted_with_saved_answers(): void
    {
        $s = $this->student();
        $quiz = $this->quiz(['time_limit_minutes' => 1]);
        $this->actingAs($s)->post(route('student.quizzes.start', $quiz));
        $attempt = Attempt::firstOrFail();
        $mc = $quiz->questions()->where('type', Question::SELECCION)->first();
        $this->postJson(route('student.attempts.save', $attempt), ['answers' => [$mc->id => ['choice' => 'o2']]])->assertOk();

        $this->travel(5)->minutes();
        $this->postJson(route('student.attempts.save', $attempt), ['answers' => [$mc->id => ['choice' => 'o1']]])->assertStatus(409);
        $this->get(route('student.attempts.take', $attempt))->assertRedirect(route('student.attempts.result', $attempt));
        $attempt->refresh();
        $this->assertNotSame(Attempt::EN_CURSO, $attempt->status);
        $this->assertEquals(1, $attempt->score);
    }

    public function test_closed_or_unpublished_quiz_cannot_be_started(): void
    {
        $s = $this->student();
        $closed = $this->quiz(['closes_at' => now()->subHour()]);
        $draft = $this->quiz(['is_published' => false]);
        $this->actingAs($s)->post(route('student.quizzes.start', $closed))->assertForbidden();
        $this->actingAs($s)->get(route('student.quizzes.show', $draft))->assertNotFound();
    }

    public function test_answers_not_revealed_before_close(): void
    {
        $s = $this->student();
        $quiz = $this->quiz(['show_results' => true, 'closes_at' => now()->addDay()]);
        $mc = $quiz->questions()->first();
        $mc->update(['feedback' => 'JUSTIFICACION-SECRETA']);
        $this->actingAs($s)->post(route('student.quizzes.start', $quiz));
        $attempt = Attempt::firstOrFail();
        $this->post(route('student.attempts.submit', $attempt), ['answers' => []]);
        $this->get(route('student.attempts.result', $attempt))->assertOk()->assertDontSee('JUSTIFICACION-SECRETA');
        $this->travel(2)->days();
        $this->get(route('student.attempts.result', $attempt))->assertOk()->assertSee('JUSTIFICACION-SECRETA');
    }

    public function test_quiz_duplication_and_question_reorder(): void
    {
        $quiz = $this->quiz();
        $this->actingAs($this->teacher)->post(route('teacher.quizzes.duplicate', $quiz))->assertRedirect();
        $copy = Quiz::latest('id')->first();
        $this->assertFalse($copy->is_published);
        $this->assertSame(4, $copy->questions()->count());

        $first = $quiz->questions()->first();
        $this->post(route('teacher.questions.move', [$first, 'abajo']));
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_web_installer_requires_token(): void
    {
        $this->get('/instalar/cualquiera')->assertNotFound();
        config(['portal.install_token' => str_repeat('a', 30)]);
        $this->get('/instalar/'.str_repeat('b', 30))->assertNotFound();
        $this->get('/instalar/'.str_repeat('a', 30))->assertOk()->assertSee('Instalación verificada');
    }
}
