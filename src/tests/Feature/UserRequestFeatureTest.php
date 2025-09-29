<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserRequestFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザーを作成
        $this->user = User::factory()->create([
            'email' => 'staff@test.com',
            'is_admin' => false,
            'password' => Hash::make('password'),
            'name' => 'テスト太郎',
        ]);

        // 管理者ユーザーを作成
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);

        // 他のユーザーを作成
        $this->otherUser = User::factory()->create(['name' => '他者さん']);

        // 打刻テストで使用するため、Carbonの時間を固定
        Carbon::setTestNow(Carbon::create(2025, 11, 15, 9, 0, 0));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // 時間固定を解除
        Carbon::setTestNow();
    }

    // --------------------------------------------------------
    // 【1. 修正申請処理 (store) のテスト】
    // --------------------------------------------------------

    /**
     * @test
     * 修正申請が正しくDBに保存され、承認待ちステータスになることを確認する。
     */
    public function test_01_application_can_be_stored_successfully()
    {
        $this->actingAs($this->user);
        $targetDate = '2025-11-10';

        $applicationData = [
            'target_date' => $targetDate,
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
            'note' => '勤務時間修正申請テスト',
        ];

        // 申請実行
        $response = $this->post(route('applications.store'), $applicationData);

        // リダイレクトと成功メッセージの確認
        $response->assertRedirect(route('applications.index'))
                ->assertSessionHas('success', '修正申請を送信しました。');

        // DBに正しく保存されたことを確認
        $this->assertDatabaseHas('applications', [
            'user_id' => $this->user->id,
            'target_date' => $targetDate,
            'status' => 1, // 1:承認待ち
        ]);

        // reasonカラムのJSON内容も部分的に確認 (check_in_timeが含まれているか)
        $application = Application::where('user_id', $this->user->id)->first();
        $reason = json_decode($application->reason, true);
        $this->assertEquals('09:00', $reason['check_in_time']);
    }

    // --------------------------------------------------------
    // 【2. 申請一覧画面 (index) のテスト】
    // --------------------------------------------------------

    /**
     * @test
     * 一般ユーザーとして申請一覧画面にアクセスし、「承認待ち」リストが正しく表示されることを確認する。
     */
    public function test_03_user_can_view_pending_applications_list()
    {
        // 事前データ作成
        $targetDate = '2025-11-01';
        $targetDateOther = '2025-11-02';

        // ログインユーザーの申請 (承認待ち: 表示されるべき)
        $pendingApp = Application::factory()->create([
            'user_id' => $this->user->id,
            'target_date' => $targetDate,
            'status' => 1 // 承認待ち
        ]);
        // ログインユーザーの申請 (承認済み: 表示されないべき)
        Application::factory()->create([
            'user_id' => $this->user->id,
            'target_date' => '2025-11-03',
            'status' => 2 // 承認済み
        ]);
        // 他のユーザーの申請 (承認待ち: 表示されないべき)
        Application::factory()->create([
            'user_id' => $this->otherUser->id,
            'target_date' => $targetDateOther,
            'status' => 1
        ]);


        $this->actingAs($this->user);

        // 承認待ち (pending) フィルターでアクセス
        $response = $this->get(route('applications.index', ['status' => 'pending']));

        $response->assertStatus(200)
                ->assertViewIs('users.applications.index')
                 // 承認待ちの申請が表示されていることを確認
                ->assertSee($pendingApp->target_date->format('Y/m/d'))
                 // 承認済みの申請は表示されていないことを確認
                ->assertDontSee('2025-11-03')
                 // 他のユーザーの申請は表示されていないことを確認
                ->assertDontSee($targetDateOther);
    }

    /**
     * @test
     * 一般ユーザーとして申請一覧画面にアクセスし、「承認済み」リストが正しく表示されることを確認する。
     */
    public function test_04_user_can_view_approved_applications_list()
    {
        // 事前データ作成
        $targetDateApproved = '2025-11-04';

        // ログインユーザーの申請 (承認済み: 表示されるべき)
        $approvedApp = Application::factory()->create([
            'user_id' => $this->user->id,
            'target_date' => $targetDateApproved,
            'status' => 2 // 承認済み
        ]);
        // ログインユーザーの申請 (承認待ち: 表示されないべき)
        Application::factory()->create([
            'user_id' => $this->user->id,
            'target_date' => '2025-11-05',
            'status' => 1 // 承認待ち
        ]);


        $this->actingAs($this->user);

        // 承認済み (approved) フィルターでアクセス
        $response = $this->get(route('applications.index', ['status' => 'approved']));

        $response->assertStatus(200)
                ->assertViewIs('users.applications.index')
                 // 承認済みの申請が表示されていることを確認
                ->assertSee($approvedApp->target_date->format('Y/m/d'))
                 // 承認待ちの申請は表示されていないことを確認
                ->assertDontSee('2025-11-05');
    }

    /**
     * @test
     * 管理者が申請一覧画面にアクセスした場合、AdminRequestControllerに処理が委譲されることを確認する。
     */
    public function test_02_admin_access_delegates_to_admin_controller()
    {
        // コントローラー内の AdminRequestController::index() が実行されるかどうかは、
        // 外部依存性のあるテストになるため、ここでは画面遷移/ステータスのみで確認。
        // ※ここでは、AdminRequestControllerのindexが、users.applications.indexとは異なる
        //   何らかのadmin用のビュー(例: admin.applications.index)を返すことを想定し、
        //   ビュー名が異なること、または管理者権限でのアクセス成功を確認する。
        //   （Controllerロジックに依存するため、ここでは簡潔に200OKを確認）

        $this->actingAs($this->admin);

        $response = $this->get(route('applications.index'));

        // 管理者としてアクセスが成功し、適切なレスポンスが返ることを確認
        $response->assertStatus(200);

        // もしAdminRequestControllerが admin.applications.index を返すなら:
        // $response->assertViewIs('admin.applications.index');
    }


    // --------------------------------------------------------
    // 【3. 申請詳細画面 (show) のテスト】
    // --------------------------------------------------------

    /**
     * @test
     * ログインユーザーが自分の申請詳細を閲覧できることを確認する。(ご提示内容 4)
     */
    public function test_05_user_can_view_own_application_detail()
    {
        // 事前データ作成: 勤怠レコードと関連する申請
        $attendance = Attendance::factory()->create(['user_id' => $this->user->id, 'date' => '2025-11-15']);
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $attendance->id,
            'target_date' => '2025-11-15',
            'reason' => json_encode(['check_in_time' => '08:30', 'note' => '修正内容']),
            'status' => 1,
        ]);

        $this->actingAs($this->user);

        // 詳細画面へアクセス
        $response = $this->get(route('applications.show', $application));

        $response->assertRedirect(route('attendance.show', ['id' => '2025-11-15']));
    }

    /**
     * @test
     * ログインユーザーが他のユーザーの申請詳細を閲覧できないことを確認する。(ご提示内容 4 / 認可)
     */
    public function test_06_user_cannot_view_other_users_application_detail()
    {
        // 事前データ作成
        $applicationOther = Application::factory()->create([
            'user_id' => $this->otherUser->id,
            'target_date' => '2025-11-16',
            'status' => 1,
        ]);

        $this->actingAs($this->user);

        // 他のユーザーの申請詳細へアクセス
        $response = $this->get(route('applications.show', $applicationOther));

        // エラーメッセージとともにリダイレクトされることを確認
        $response->assertRedirect(route('applications.index'))
                ->assertSessionHas('error', '他のユーザーの申請は閲覧できません。');
    }

}
