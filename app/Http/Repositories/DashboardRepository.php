<?php

namespace App\Http\Repositories;

use App\Models\Question;
use App\Models\User;
use App\Models\Subject;
use App\Models\UserTest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    protected $mUser;
    protected $mSubject;
    protected $mQuestion;
    protected $mUserTest;
    protected $carbon;

    public const MONTH = [
        1 => "ENERO",
        2 => "FEBRERO",
        3 => "MARZO",
        4 => "ABRIL",
        5 => "MAYO",
        6 => "JUNIO",
        7 => "JULIO",
        8 => "AGOSTO",
        9 => "SEPTIEMBRE",
        10 => "OCTUBRE",
        11 => "NOVIEMBRE",
        12 => "DICIEMBRE"
    ];

    public function __construct()
    {
        $this->mUser = new User();
        $this->mSubject = new Subject();
        $this->mQuestion = new Question();
        $this->carbon = new Carbon();
        $this->mUserTest = new UserTest();
    }

    public function getAllUsers()
    {
        return $this->mUser->select('*')
            ->where('type_id', User::TYPES['student'])
            ->get();
    }

    public function getStats()
    {
        $countUsers = $this->mUser
            ->where('type_id', User::TYPES['student'])
            ->whereNull('deleted_at')
            ->count();

        $countAdmins = $this->mUser
            ->where('type_id', User::TYPES['admin'])
            ->count();

        $countSubjects = $this->mSubject
            ->whereNull('deleted_at')
            ->count();

        $countQuestions = $this->mQuestion
            ->whereNull('deleted_at')
            ->count();
        $now = Carbon::now();
        $last = Carbon::now()->subMinutes(10);
        $command = "sudo aws cloudwatch get-metric-statistics --namespace AWS/EC2 --metric-name CPUUtilization  --period 3600 --statistics Maximum --dimensions Name=InstanceId,Value=i-0b5983b042c435098 --start-time " . $last->toISOString() . " --end-time " . $now->toISOString();
        $responseCommand = shell_exec($command);
        $cpuUsage = json_decode($responseCommand);
        if (count($cpuUsage->Datapoints) > 0) {
            $keyLast = array_key_last($cpuUsage->Datapoints);
        }

        return [
            'countUsers' => $countUsers,
            'countAdmins' => $countAdmins,
            'countSubjects' => $countSubjects,
            'countQuestions' => $countQuestions,
            'cpuUsage' => count($cpuUsage->Datapoints) > 0 ? round($cpuUsage->Datapoints[$keyLast]->Maximum, 2) . '%' : '0.1%'
        ];
    }

    public function getBarChart()
    {
        $questions = DB::select('CALL subjects_count_test_global');
        $subjectName = [];
        $reps = [];
        foreach ($questions as $subject) {
            $subjectName[] = $subject->name;
            $reps[] = $subject->repeticion;
        }

        return [
            'labels' => $subjectName,
            'info' => $reps
        ];
    }

    public function getUserProgress($id)
    {
        return $this->mUser->select('subjects.name', 'user_tests.*')
            ->join('user_tests', 'users.id', '=', 'user_tests.user_id')
            ->join('subjects', 'subjects.id', '=', 'user_tests.subject_id')
            ->where('users.id', $id)
            ->where('users.type_id', User::TYPES['student'])
            ->whereNull('users.deleted_at')
            ->get();
    }

    public function getBalance($request)
    {
        $dateInit = $this->carbon->parse($request->date)->format('Y-01-01');
        $dateEnd = $this->carbon->parse($request->date)->format('Y-12-31');
        $balance = DB::select('CALL balance_per_year("' . $dateInit . '","' . $dateEnd . '")');
        $months = [];
        $amounts = [];

        foreach ($balance as $bal) {
            $months[] = self::MONTH[$bal->meses];
            $amounts[] = $bal->monto;
        }

        return [
            'labels' => $months,
            'info' => $amounts
        ];
    }
}
