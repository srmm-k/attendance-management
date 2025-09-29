<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime; // BreakTimeモデルを追加
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash; // パスワードのハッシュ化用
use Database\Factories\BreakTimeFactory;

class AdminAttendanceFeatureTest extends TestCase
{
    // テスト実行後にデータベースをリセット
    use DatabaseMigrations;

    /**
     * セットアップ処理（テストごとに実行）
     */
    protected function setUp(): void
    {
        parent::setUp();

        // データベースに必ず存在するAdminユーザーと一般ユーザーを作成
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com', // 識別しやすいメールアドレス
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);
        $this->targetUser = User::factory()->create([
            'email' => 'user@test.com',
            'is_admin' => false,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @test
     * その日になされた全ユーザーの勤怠情報が正確に確認できるか (テスト1)
     */
    public function index_displays_all_users_attendance_for_the_given_date()
    {
        // テスト用の日付を設定
        $targetDate = Carbon::today()->toDateString();

        // 別の一般ユーザーをもう一人作成
        $user2 = User::factory()->create(['is_admin' => false]);

        // ターゲット日付の勤怠データを作成
        $attendance1 = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => $targetDate,
            'check_in_time' => '09:00:00',
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => $targetDate,
            'check_in_time' => '10:00:00', // 別の時間
        ]);

        // 別の日の勤怠データを作成（表示されないことを確認するため）
        Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => Carbon::yesterday()->toDateString(),
        ]);

        // 管理者としてアクセス (クエリパラメータなしで今日の日付を検証)
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.attendances.list'));

        // 成功とビューの使用を確認
        $response->assertStatus(200)
                ->assertViewIs('admin.attendances.index');

        // ページ内にターゲット日付の2件の勤怠情報が表示されていることを確認
        $response->assertSee($attendance1->user->name)
                ->assertSee($attendance2->user->name)
                 ->assertSee('09:00') // 時刻の一部
                ->assertSee('10:00');
    }

    /**
     * @test
     * 管理者としてログインして勤怠一覧画面へ遷移した時に現在の日付が表示されるか (テスト2)
     */
    public function index_displays_current_date_when_no_query_is_given()
    {
        $todayFormatted = Carbon::today()->format('Y/m/d');

        // 管理者としてアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.attendances.list'));

        // 現在の日付 (Y年M月D日形式など、Bladeで表示される形式) が含まれていることを確認
        // Bladeの表示形式に合わせてアサーションを調整してください。ここではY-m-d形式をチェック
        $response->assertStatus(200)
                ->assertSee($todayFormatted);
    }

    /**
    * @test
     * 前日を押した時に前の日の勤怠情報が表示されるか (テスト3)
     */
    public function index_displays_previous_days_attendance_when_date_is_set()
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $yesterdayFormatted = Carbon::yesterday()->format('Y/m/d');;

        // 前日の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => $yesterday,
        ]);

        // 管理者として昨日の日付をクエリパラメータで指定してアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.attendances.list', ['date' => $yesterday]));

        // 昨日の日付が表示され、その日のデータが表示されていることを確認
        $response->assertStatus(200)
                 ->assertSee($yesterdayFormatted) // URLパラメータの日付が表示されている
                ->assertSee($attendance->user->name);

        // 画面に「前日」リンクが含まれ、そのリンク先URLに前々日の日付が含まれていることを確認
        $prevDay = Carbon::yesterday()->subDay()->toDateString();
        $response->assertSee(route('admin.attendances.list', ['date' => $prevDay]));
    }

    /**
     * @test
     * 翌日を押した時に次の日の勤怠情報が表示されるか (テスト4)
     */
    public function index_displays_next_days_attendance_when_date_is_set()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $tomorrowFormatted = Carbon::tomorrow()->format('Y/m/d');

        // 翌日の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => $tomorrow,
        ]);

        // 管理者として明日の日付をクエリパラメータで指定してアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.attendances.list', ['date' => $tomorrow]));

        // 明日の日付が表示され、その日のデータが表示されていることを確認
        $response->assertStatus(200)
                 ->assertSee($tomorrowFormatted) // URLパラメータの日付が表示されている
                ->assertSee($attendance->user->name);

        // 画面に「翌日」リンクが含まれ、そのリンク先URLに明後日の日付が含まれていることを確認
        $nextDay = Carbon::tomorrow()->addDay()->toDateString();
        $response->assertSee(route('admin.attendances.list', ['date' => $nextDay]));
    }

    /**
     * @test
     * 対象のユーザーの欄にある詳細をクリックした時に対象のユーザーのその日付の勤怠詳細画面へ遷移できるか (テスト5)
     */
    public function can_navigate_to_attendance_show_screen_by_id()
    {
        // 勤怠データを作成（詳細画面のテストのため）
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => Carbon::today()->toDateString(),
            'check_in_time' => '09:00:00',
        ]);

        // 詳細画面へのアクセスをシミュレート
        // show ルートは web.php で 'attendance.show' と定義されています
        $response = $this->actingAs($this->admin)
                        ->get(route('attendance.show', ['id' => $attendance->id]));

        // 成功とビューの使用を確認
        $response->assertStatus(200)
                ->assertViewIs('admin.attendances.show'); // showビューを返していることを確認

        // 詳細画面に、ユーザー名、日付、出勤時刻が表示されていることを確認
        $response->assertSee($this->targetUser->name)
                ->assertSee($attendance->date->toDateString())
                ->assertSee('09:00');
    }

    /**
     * @test
     * 勤怠詳細画面に表示されているデータが選択したものになっているか (テスト1: 表示の正確性)
     *
     * (既存の can_navigate_to_attendance_show_screen_by_id と重複するため、それを調整・強化)
     */
    public function show_displays_correct_attendance_data()
    {
        // テスト用の勤怠データを作成
        $targetDate = Carbon::today()->toDateString();
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => $targetDate,
            'check_in_time' => '09:30:00',
            'check_out_time' => '18:30:00',
        ]);
        // 休憩データを追加
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in_time' => '12:00:00',
            'break_out_time' => '13:00:00',
        ]);

        // 管理者としてアクセス
        // ルートは共通の 'attendance.show' を使用
        $response = $this->actingAs($this->admin)
                        ->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertStatus(200)
                ->assertViewIs('admin.attendances.show');

        // ユーザー名と日付の確認
        $response->assertSee($this->targetUser->name)
                ->assertSee($targetDate);

        // 勤怠時刻の確認
        $response->assertSee('09:30', false) // 出勤
                 ->assertSee('18:30', false); // 退勤

        // 休憩時間の確認
        $response->assertSee('12:00', false) // 休憩開始
                 ->assertSee('13:00', false); // 休憩終了
    }


    /**
     * @test
     * 出勤時間が退勤時間よりも後になっている場合、エラーメッセージが表示されるか (テスト2: バリデーション)
     */
    public function update_fails_when_check_in_time_is_after_check_out_time()
    {
        // 既存の勤怠データを作成（更新対象）
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        // 不正なデータ: 出勤時間(18:00)が退勤時間(09:00)より後の場合
        $invalidData = [
            'check_in_time' => '18:00',
            'check_out_time' => '09:00',
            'breaks' => [],
            'note' => '修正申請テスト',
        ];

        $response = $this->actingAs($this->admin)
                    ->put(route('attendance.update', ['id' => $attendance->id]), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['check_in_time']);
    }

    /**
    * @test
     * 休憩開始時間が退勤時間よりも後になっている場合、エラーメッセージが表示されるか (テスト3: バリデーション)
     */
    public function update_fails_when_break_in_time_is_after_check_out_time()
    {
        // 既存の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => Carbon::today()->toDateString(),
            'check_in_time' => '09:00',
            'check_out_time' => '17:00', // 退勤時間
        ]);

        // 不正なデータ: 休憩開始時間(18:00)が退勤時間(17:00)より後の場合
        $invalidData = [
            'check_in_time' => '09:00',
            'check_out_time' => '17:00',
            'breaks' => [
                ['break_in_time' => '18:00', 'break_out_time' => '19:00'] 
            ],
            'note' => '休憩時間バリデーションテスト',
        ];

        $response = $this->actingAs($this->admin)
                    ->put(route('attendance.update', ['id' => $attendance->id]), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.break_in_time']);
    }


    /**
     * @test
     * 休憩終了時間が退勤時間よりも後になっている場合、エラーメッセージが表示されるか (テスト4: バリデーション)
     */
    public function update_fails_when_break_out_time_is_after_check_out_time()
    {
        // 既存の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->targetUser->id,
            'date' => Carbon::today()->toDateString(),
            'check_in_time' => '09:00',
            'check_out_time' => '17:00', // 退勤時間
        ]);

        // 不正なデータ: 休憩終了時間(18:00)が退勤時間(17:00)より後の場合
        $invalidData = [
            'check_in_time' => '09:00',
            'check_out_time' => '17:00',
            'breaks' => [
                ['break_in_time' => '12:00', 'break_out_time' => '18:00'] 
            ],
            'note' => '休憩時間バリデーションテスト',
        ];

        $response = $this->actingAs($this->admin)
                    ->put(route('attendance.update', ['id' => $attendance->id]), $invalidData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['breaks.0.break_out_time']);
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示されるか (テスト5: バリデーション)
     */
    public function update_fails_when_note_is_missing()
    {
    // 既存の勤怠データを作成（更新対象）
    $attendance = Attendance::factory()->create([
        'user_id' => $this->targetUser->id,
        'date' => Carbon::today()->toDateString(),
    ]);

    // 不正なデータ: note（備考）が空の場合
    $invalidData = [
        'check_in_time' => '09:00',
        'check_out_time' => '18:00',
        'breaks' => [],
        'note' => '', // 未入力
    ];

    $response = $this->actingAs($this->admin)
                    ->put(route('attendance.update', ['id' => $attendance->id]), $invalidData);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['note']);

    }
}