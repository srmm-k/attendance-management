<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserAttendanceFeatureTest extends TestCase
{
    // テスト実行後にデータベースをリセット
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザーとして一般ユーザーを作成
        $this->user = User::factory()->create([
            'email' => 'staff@test.com',
            'is_admin' => false,
            'password' => Hash::make('password'),
            'name' => 'テスト太郎',
        ]);

        // 打刻テストで使用するため、Carbonの時間を固定
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 9, 0, 0));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // 時間固定を解除
        Carbon::setTestNow();
    }

    // --------------------------------------------------------
    // 【勤怠登録画面 (create) / ステータス表示のテスト】 (テスト項目: 1〜5)
    // --------------------------------------------------------

    /**
     * @test
     * 勤怠登録画面の表示と、現在の勤怠ステータス（勤務外、出勤中、休憩中、退勤済）の表示を確認する。
     */
    public function test_01_create_page_status_and_time_display()
    {
        $this->actingAs($this->user);

        // 1. 勤務外の場合 (レコードなし)
        // 期待: 現在時刻が表示され、ステータスが「勤務外」であること
        $response = $this->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSee(Carbon::now()->format('Y年m月d日')) // 現在の日時
                 ->assertSee('勤務外');                           // 勤怠登録 1, 2

        // 2. 出勤中の場合 (check_in_time のみ)
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 9, 0, 0));
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'check_in_time' => '09:00:00',
            'check_out_time' => null,
        ]);
        $response = $this->get(route('attendance.create'));
        $response->assertSee('出勤中'); // 勤怠登録 3

        // 3. 休憩中の場合 (check_in_time あり、休憩入りのみ)
        BreakTime::factory()->create([
            'attendance_id' => $this->user->attendances->first()->id,
            'break_in_time' => '12:00:00',
            'break_out_time' => null,
        ]);
        $response = $this->get(route('attendance.create'));
        $response->assertSee('休憩中'); // 勤怠登録 4

        // 4. 退勤済みの場合 (check_out_time あり)
        $attendance = $this->user->attendances->first();
        $attendance->check_out_time = '18:00:00';
        $attendance->save();
        $response = $this->get(route('attendance.create'));
        $response->assertSee('退勤済'); // 勤怠登録 5
    }

    // --------------------------------------------------------
    // 【打刻機能 (store) のテスト】 (テスト項目: 6〜16)
    // --------------------------------------------------------

    /**
     * @test
     * 出勤、休憩、退勤の打刻処理がロジック通りに機能することを確認する。
     */
    public function test_02_attendance_stamping_actions()
    {
        $this->actingAs($this->user);
        $attendanceId = null;

        // 【出勤テスト】
        // --------------------------------------------------------
        // 勤怠登録 6: 出勤ボタンが正しく機能する
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 9, 0, 0));
        $response = $this->post(route('attendance.store'), ['checkin' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '出勤時間を記録しました。');

        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('09:00:00', $attendance->check_in_time);
        $attendanceId = $attendance->id; // 後続テストで使用

        // 勤怠登録 7: 出勤は一日一回のみできる
        $response = $this->post(route('attendance.store'), ['checkin' => '']);
        $response->assertRedirect()
                ->assertSessionHas('error', '既に出勤済みです。');

        // 【休憩テスト 1回目】
        // --------------------------------------------------------
        // 勤怠登録 9: 休憩ボタンが正しく機能する (休憩入)
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 12, 0, 0));
        $response = $this->post(route('attendance.store'), ['break_in' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '休憩を開始しました。');
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendanceId,
            'break_in_time' => '12:00:00',
            'break_out_time' => null,
        ]);

        // 勤怠登録 12: 休憩戻ボタンが正しく機能する (休憩戻)
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 13, 0, 0));
        $response = $this->post(route('attendance.store'), ['break_out' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '休憩を終了しました。');
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendanceId,
            'break_in_time' => '12:00:00',
            'break_out_time' => '13:00:00',
        ]);

        // 【休憩テスト 2回目】
        // --------------------------------------------------------
        // 勤怠登録 10: 休憩は一日に何回でもできる (休憩入)
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 15, 0, 0));
        $response = $this->post(route('attendance.store'), ['break_in' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '休憩を開始しました。');

        // 勤怠登録 11: 休憩戻ボタンが正しく機能する (休憩戻)
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 15, 30, 0));
        $response = $this->post(route('attendance.store'), ['break_out' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '休憩を終了しました。');

        // 【退勤テスト】
        // --------------------------------------------------------
        // 勤怠登録 14: 退勤ボタンが正しく機能する
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 18, 0, 0));
        $response = $this->post(route('attendance.store'), ['checkout' => '']);
        $response->assertRedirect()
                ->assertSessionHas('status', '退勤時間を記録しました。');

        $attendance->refresh();
        $this->assertEquals('18:00:00', $attendance->check_out_time);

        // 休憩時間の計算を確認 (1回目: 60分, 2回目: 30分 -> 合計90分)
        $this->assertEquals(90, $attendance->break_time); 
        // 勤務時間計算を確認 (9:00-18:00 = 540分 - 90分休憩 = 450分)
        $this->assertEquals(450, $attendance->total_time);

        // 勤怠登録 16: 退勤は一日一回のみできる
        $response = $this->post(route('attendance.store'), ['checkout' => '']);
        $response->assertRedirect()
                ->assertSessionHas('error', '未出勤または既に退勤済みです。');
    }

    // --------------------------------------------------------
    // 【勤怠一覧画面 (index) のテスト】 (テスト項目: 勤怠一覧 1〜5)
    // --------------------------------------------------------

    /**
     * @test
     * 勤怠一覧画面の表示、データ表示、月別ナビゲーションを確認する。
     */
    public function test_03_attendance_list_and_navigation()
    {
        $this->actingAs($this->user);

        // 勤怠データを作成 (10月と9月)
        Attendance::factory()->create(['user_id' => $this->user->id, 'date' => '2025-10-15', 'check_in_time' => '09:00:00']);
        Attendance::factory()->create(['user_id' => $this->user->id, 'date' => '2025-09-10', 'check_in_time' => '08:00:00']);

        // 1. 現在の月（10月）の表示とデータ表示
        Carbon::setTestNow(Carbon::create(2025, 10, 20));
        $response = $this->get(route('attendance.list')); // 勤怠一覧画面 2
        $response->assertStatus(200)
                ->assertViewIs('users.attendances.index')
                ->assertSee('2025/10') // 表示月の確認
                ->assertSee('10/15')      // 勤怠一覧画面 1: 自分の勤怠情報
                ->assertSee('09:00');     // 勤怠一覧画面 1: 自分の勤怠情報

        // 2. 前月への遷移
        $response = $this->get(route('attendance.list', ['year' => 2025, 'month' => 9])); // 勤怠一覧画面 3
        $response->assertStatus(200)
                ->assertSee('2025/09') // 表示月の確認
                ->assertSee('9/10')       // 前月のデータが表示されている
                ->assertSee('08:00');

        // 3. 翌月への遷移 (9月から10月へ)
        $response = $this->get(route('attendance.list', ['year' => 2025, 'month' => 10])); // 勤怠一覧画面 4
        $response->assertStatus(200)
                ->assertSee('2025/10');

        // 4. 詳細画面への遷移 (テスト項目: 勤怠一覧画面 5)
        $attendance = $this->user->attendances->where('date', '2025-10-15')->first();
        $response = $this->get(route('attendance.list'));
        // 詳細ボタンが正しいURL（日付形式）を指していることを確認
        $response->assertSee(route('attendance.show', ['id' => '2025-10-15']));
    }

    // --------------------------------------------------------
    // 【勤怠詳細 (show/update) のテスト】 (テスト項目: 勤怠詳細 1〜4, 修正 1〜4)
    // --------------------------------------------------------

    /**
     * @test
     * 勤怠詳細画面の表示、情報の正確性、および修正申請のバリデーションを確認する。
     */
    public function test_04_attendance_detail_and_application()
    {
        $this->actingAs($this->user);

        // 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-10-10',
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in_time' => '12:00:00',
            'break_out_time' => '13:00:00',
        ]);

        // 【情報取得のテスト】
        // --------------------------------------------------------
        $response = $this->get(route('attendance.show', ['id' => $attendance->date->toDateString()]));
        $response->assertStatus(200)
                ->assertViewIs('users.attendances.show');

        // 勤怠詳細 1: 名前
        $response->assertSee('テスト太郎');
        // 勤怠詳細 2: 日付
        $response->assertSee('2025年');
        $response->assertSee('10月10日');
        // 勤怠詳細 3: 出勤・退勤
        $response->assertSee('09:00');
        $response->assertSee('17:00');
        // 勤怠詳細 4: 休憩
        $response->assertSee('12:00');
        $response->assertSee('13:00');


        // 【情報修正（バリデーション）のテスト】
        // --------------------------------------------------------

        // 修正申請用のデータ (ここでは正常なデータをベースとする)
        $validData = [
            'check_in_time' => '09:30',
            'check_out_time' => '18:30',
            'note' => '勤務時間修正申請',
            'breaks' => [
                ['break_in_time' => '12:00', 'break_out_time' => '13:00'],
            ]
        ];

        // 勤怠詳細（情報修正） 4: 備考欄が未入力の場合のエラー
        $invalidNoteData = array_merge($validData, ['note' => '']);
        $response = $this->put(route('attendance.update', ['id' => $attendance->date->toDateString()]), $invalidNoteData);
        $response->assertSessionHasErrors(['note' => '備考を記入してください']);

        // 勤怠詳細（情報修正） 1: 出勤 > 退勤の場合のエラー
        $invalidTimeData = array_merge($validData, [
            'check_in_time' => '19:00',
            'check_out_time' => '18:00',
            'note' => 'エラーテスト'
        ]);
        $response = $this->put(route('attendance.update', ['id' => $attendance->date->toDateString()]), $invalidTimeData);
        $response->assertSessionHasErrors(['check_in_time' => '出勤時間もしくは退勤時間が不適切な値です']);

        // 勤怠詳細（情報修正） 2: 休憩開始 > 退勤の場合のエラー
        $invalidBreakInData = array_merge($validData, [
            'check_out_time' => '15:00',
            'breaks' => [
                ['break_in_time' => '16:00', 'break_out_time' => '17:00'],
            ],
            'note' => 'エラーテスト'
        ]);
        $response = $this->put(route('attendance.update', ['id' => $attendance->date->toDateString()]), $invalidBreakInData);
        $response->assertSessionHasErrors(['breaks.0.break_in_time' => '休憩時間が不適切な値です']);

        // 勤怠詳細（情報修正） 3: 休憩終了 > 退勤の場合のエラー
        $invalidBreakOutData = array_merge($validData, [
            'check_out_time' => '15:00',
            'breaks' => [
                ['break_in_time' => '14:00', 'break_out_time' => '16:00'],
            ],
            'note' => 'エラーテスト'
        ]);
        $response = $this->put(route('attendance.update', ['id' => $attendance->date->toDateString()]), $invalidBreakOutData);
        $response->assertSessionHasErrors(['breaks.0.break_out_time' => '休憩時間もしくは退勤時間が不適切な値です']);

        // 正常な申請がDBに保存されることを確認
        $response = $this->put(route('attendance.update', ['id' => $attendance->date->toDateString()]), $validData);
        $response->assertRedirect()
                ->assertSessionHas('status', '勤怠情報を修正申請しました。');

        $this->assertDatabaseHas('applications', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->user->id,
            'status' => 1, // 承認待ち
            'target_date' => '2025-10-10',
        ]);
    }
}
