<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Application;
use App\Models\BreakTime;
use Database\Factories\ApplicationFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminRequestFeatureTest extends TestCase
{
    // テスト実行後にデータベースをリセット
    use DatabaseMigrations;

    /**
     * テストごとの初期設定
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Adminユーザーと一般ユーザー（スタッフ）を作成
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);
        $this->staffUser = User::factory()->create([
            'email' => 'staff@test.com',
            'is_admin' => false,
            'password' => Hash::make('password'),
            'name' => '申請太郎',
        ]);

        // 申請が紐づく元の勤怠データを作成
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->staffUser->id,
            'date' => Carbon::today()->toDateString(),
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
            'total_time' => 480, // 8時間
        ]);

        // 休憩データも作成
        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_in_time' => '12:00:00',
            'break_out_time' => '13:00:00',
        ]);

        // 別の一般ユーザーを作成（テスト1, 2用）
        $this->userB = User::factory()->create(['is_admin' => false]);

        // 承認待ちの申請 (Status: 1)
        $this->pendingApplication = Application::factory()->create([
            'status' => 1,
            'user_id' => $this->staffUser->id,
            'attendance_id' => $this->attendance->id,
            'target_date' => $this->attendance->date->toDateString(),
        ]);

        // 承認済みの申請 (Status: 2)
        $this->approvedApplication = Application::factory()->create([
            'status' => 2,
            'user_id' => $this->staffUser->id,
            'attendance_id' => $this->attendance->id,
        ]);

        // 別のユーザーの承認待ち申請 (テスト1用)
        Application::factory()->create([
            'status' => 1,
            'user_id' => $this->userB->id,
            'attendance_id' => Attendance::factory()->create(['user_id' => $this->userB->id])->id,
        ]);
    }

    /**
     * @test
     * 全てのユーザーの承認待ちの修正申請が全て表示されている (テスト1)
     */
    public function index_displays_only_pending_applications_by_default()
    {
        // 管理者として申請一覧にアクセス (クエリなし、デフォルトでstatus=pending)
        $response = $this->actingAs($this->admin)
                        ->get(route('applications.index'));

        $response->assertStatus(200)
                ->assertViewIs('admin.applications.index');

        // 承認待ちの申請が表示されていることを確認 (userBの申請も含め2件)
        $response->assertSee('承認待ち')
                ->assertSee($this->pendingApplication->user->name)
                ->assertSee($this->userB->name);

    }

    /**
     * @test
     * 全てのユーザーの承認済みの修正申請が全て表示されている (テスト2)
     */
    public function index_displays_only_approved_applications_when_status_is_approved()
    {
        // 管理者として申請一覧にアクセス (status=approvedをクエリで指定)
        $response = $this->actingAs($this->admin)
                        ->get(route('applications.index', ['status' => 'approved']));

        $response->assertStatus(200)
                ->assertViewIs('admin.applications.index');

        // 承認済みの申請が表示されていることを確認
        $response->assertSee('承認済み')
                ->assertSee($this->approvedApplication->user->name)
                ->assertDontSee($this->userB->name);

    }

    /**
     * @test
     * 申請者の勤怠詳細画面を開き、修正申請の詳細内容が正しく表示されている (テスト3)
     */
    public function show_displays_application_details_correctly()
    {
        // 申請の詳細画面へアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.requests.detail', ['attendance_correct_request' => $this->pendingApplication->id]));

        $response->assertStatus(200)
                ->assertViewIs('admin.applications.approval');

        // 申請の詳細内容 (reasonのJSON) が表示されていることを確認
        $reasonData = json_decode($this->pendingApplication->reason, true);

        // 修正後の出勤・退勤時刻、備考などが含まれているかを確認
        $response->assertSee($this->pendingApplication->user->name) // 申請者名
                 ->assertSee($reasonData['check_in_time'])         // 申請された出勤時刻
                 ->assertSee($reasonData['check_out_time'])        // 申請された退勤時刻
                 ->assertSee($reasonData['note']);                  // 申請された備考
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる (テスト4: 承認処理とデータ更新)
     */
    public function update_approves_application_and_updates_attendance_data()
    {
        $application = $this->pendingApplication;
        $attendance = $this->attendance;

        // 申請時のJSONデータ
        $reasonData = json_decode($application->reason, true);

        // 修正申請された休憩データ (12:00-13:00の60分休憩) があることを確認
        $this->assertEquals(1, $attendance->breaks()->count()); // 初期値: 休憩1件

        // 1. 管理者として承認リクエストを送信 (status=2)
        $response = $this->actingAs($this->admin)
                        ->put(route('admin.requests.update', ['attendance_correct_request' => $application->id]), [
                            'status' => 2,
                        ]);

        // 2. リダイレクトとサクセスメッセージを確認
        $response->assertRedirect(route('applications.index'))
                ->assertSessionHas('status', '申請を承認しました。');

        // 3. 申請ステータスが「承認済み」(2)に更新されたことをDBで確認
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 2,
        ]);

        // 4. 勤怠データが申請内容に更新されたことをDBで確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_in_time' => $reasonData['check_in_time'] . ':00', // 09:00 -> 09:00:00 になる想定
            'check_out_time' => $reasonData['check_out_time'] . ':00', // 18:00 -> 18:00:00 になる想定
            'note' => $reasonData['note'],
            'break_time' => 60, // 休憩合計時間 (12:00-13:00の60分)
            'total_time' => 480, // 勤務時間合計 (9時間-1時間休憩 = 8時間 = 480分)
        ]);

        // 5. 休憩データが正しく再登録されていることをDBで確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_in_time' => '12:00:00',
            'break_out_time' => '13:00:00',
        ]);

        // 休憩データの件数が変わっていないことを確認（delete -> create が正しく行われたか）
        $this->assertEquals(1, $attendance->breaks()->count());

    }
}
