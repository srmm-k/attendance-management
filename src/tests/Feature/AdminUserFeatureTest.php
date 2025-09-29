<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AdminUserFeatureTest extends TestCase
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
            'name' => 'テスト太郎', // 特定しやすい名前
        ]);
    }

    /**
     * @test
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」が正しく表示され確認できる事 (テスト1)
     */
    public function index_displays_all_non_admin_users_data()
    {
        // 別の一般ユーザーを作成
        $userB = User::factory()->create(['is_admin' => false, 'name' => 'テスト花子', 'email' => 'hanako@example.com']);

        // 管理者としてスタッフ一覧へアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.users'));

        $response->assertStatus(200)
                ->assertViewIs('admin.staff_list');

        // 作成した一般ユーザー全員の氏名とメールアドレスが表示されていることを確認
        $response->assertSee('テスト太郎') // setUpで作成したユーザー
                ->assertSee('staff@test.com')
                 ->assertSee('テスト花子') // 新規作成したユーザー
                ->assertSee('hanako@example.com');

        // 管理者自身の情報は表示されないことを確認
        $response->assertDontSee($this->admin->name);
    }

    // --- スタッフ別勤怠一覧画面（UserController@showAttendances）のテスト ---

    /**
     * @test
     * 選択したユーザーの登録してある勤怠情報が正しく表示される (テスト2: 遷移とデータ表示)
     */
    public function showAttendances_displays_correct_users_attendance_data()
    {
        $targetDate = Carbon::now()->startOfMonth()->toDateString();

        // ターゲットユーザー（$this->staffUser）の勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->staffUser->id,
            'date' => $targetDate,
            'check_in_time' => '09:00:00',
            'check_out_time' => '18:00:00',
            'total_time' => 540, // 9時間 x 60分
        ]);

        // 別ユーザーの勤怠データを作成（表示されないことを確認）
        $otherUser = User::factory()->create(['is_admin' => false]);
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => $targetDate,
            'check_in_time' => '11:00:00', // 違う時刻
        ]);

        // 管理者として、ターゲットユーザーの勤怠一覧へアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.users.attendances', ['id' => $this->staffUser->id]));

        $response->assertStatus(200)
                ->assertViewIs('admin.staff_attendance_list');

        // 1. ターゲットユーザー名が表示されていることを確認
        $response->assertSee($this->staffUser->name);

        // 2. ターゲットユーザーの勤怠データが表示されていることを確認
        $response->assertSee($attendance->date->format('d')); // 日付の「日」部分
        $response->assertSee('09:00'); // 正しい出勤時間
        $response->assertSee('18:00'); // 正しい退勤時間

        // 3. 別ユーザーの情報が表示されていないことを確認
        $response->assertDontSee('11:00');
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される (テスト3)
     */
    public function showAttendances_navigates_to_previous_month()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $prevMonth = Carbon::now()->subMonth();
        $prevMonthUrl = route('admin.users.attendances', ['id' => $this->staffUser->id, 'year' => $prevMonth->year, 'month' => $prevMonth->month]);

        // 前月の勤怠データを作成 (表示されることを確認するため)
        Attendance::factory()->create([
            'user_id' => $this->staffUser->id,
            'date' => $prevMonth->day(15)->toDateString(),
            'check_in_time' => '07:00:00', // 確認用時刻
        ]);

        // 管理者として現在の月でアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.users.attendances', ['id' => $this->staffUser->id]));

        // 画面に前月へのリンク（URL）が含まれていることを確認
        $response->assertSee($prevMonthUrl);

        // 前月のURLに直接アクセス
        $response = $this->actingAs($this->admin)
                        ->get($prevMonthUrl);

        // 前月のデータ（07:00）が表示されていることを確認
        $response->assertStatus(200)
                ->assertSee('07:00');
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される (テスト4)
     */
    public function showAttendances_navigates_to_next_month()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $nextMonth = Carbon::now()->addMonth();
        $nextMonthUrl = route('admin.users.attendances', ['id' => $this->staffUser->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]);

        // 翌月の勤怠データを作成 (表示されることを確認するため)
        Attendance::factory()->create([
            'user_id' => $this->staffUser->id,
            'date' => $nextMonth->day(15)->toDateString(),
            'check_in_time' => '11:30:00', // 確認用時刻
        ]);

        // 管理者として現在の月でアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.users.attendances', ['id' => $this->staffUser->id]));

        // 画面に翌月へのリンク（URL）が含まれていることを確認
        $response->assertSee($nextMonthUrl);

        // 翌月のURLに直接アクセス
        $response = $this->actingAs($this->admin)
                        ->get($nextMonthUrl);

        // 翌月のデータ（11:30）が表示されていることを確認
        $response->assertStatus(200)
                ->assertSee('11:30');

    }
    /**
     * @test
     * 勤怠データがCSVで正しくエクスポートされるか (テスト5: CSVエクスポート機能)
     */
    public function test_export_csv_downloads_correct_file_with_correct_data()
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $targetDate = Carbon::create($year, $month, 10)->toDateString();
        $targetUser = $this->staffUser;

        // 1. ターゲットユーザーの勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $targetUser->id,
            'date' => $targetDate,
            'check_in_time' => '09:00:00',
            'check_out_time' => '18:00:00',
            'break_time' => 60, // 60分
            'total_time' => 480, // 8時間 = 480分
            'note' => '特別な勤務日',
        ]);

        // 2. 他のユーザーのデータを作成 (CSVに含まれないことを確認するため)
        $otherUser = User::factory()->create(['is_admin' => false]);
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => $targetDate,
            'check_in_time' => '11:00:00',
            'total_time' => 300,
        ]);


        // 3. 管理者としてCSVエクスポートルートにアクセス
        $response = $this->actingAs($this->admin)
                        ->get(route('admin.users.attendances.exportCsv', [
                            'id' => $targetUser->id,
                            'year' => $year,
                            'month' => $month
                        ]));

        // 4. ダウンロードレスポンスの検証
        $fileName = "{$targetUser->name}_{$year}年{$month}月_勤怠データ.csv";

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
                 // ファイル名の検証 (Content-Dispositionヘッダーのチェック)
                ->assertHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');


        // 5. CSVファイルの内容の検証
        $content = $response->streamedContent();

        // BOMを除去
        $content = ltrim($content, "\xEF\xBB\xBF");

        // CSVを行ごとに分割
        $lines = explode("\n", trim($content));

        // ヘッダー行の検証
        $expectedHeader = [
            '日付', '出勤時間', '退勤時間', '休憩開始１', '休憩終了１',
            '休憩開始２', '休憩終了２', '合計休憩時間（分）', '合計勤務時間（分）', '備考'
        ];
        $this->assertEquals($expectedHeader, str_getcsv($lines[0]));

        // データ行の検証
        $expectedData = [
            $targetDate . ' 00:00:00',
            '09:00:00', // DBの形式に合わせる
            '18:00:00',
            '', // ブレイクは作成していないためnull
            '',
            '',
            '',
            '60',
            '480',
            '特別な勤務日',
        ];
        $this->assertEquals($expectedData, str_getcsv($lines[1]));

        // 他のデータ行が存在しないことを確認 (行数がヘッダー+1データ行のみ)
        $this->assertCount(2, $lines);
    }
}