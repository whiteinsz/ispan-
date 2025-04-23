<?php

namespace App\Traits;


use Illuminate\Support\Facades\DB;

trait ScoreSync
{
    public function syncScoreWithDatabase($request,$user)
    {
        
        $averageScores = $request ->averageScores;
         // 如果 $averageScores 是空陣列，則不更新
        if (empty($averageScores)) {
            return "No update, empty scores.";
        }  // 檢查資料庫是否有資料
            $existingUserScores = DB::table('user_scores')
                ->where('user_id', $user->id)
                ->first();

            if ($existingUserScores) {
                // 更新數量
                DB::table('user_scores')
                    ->where('id', $existingUserScores->id)
                    ->update([
                        'averageScores' => $averageScores,
                        'updated_at' => now()
                    ]);
                    // 回傳資料庫的資料
                    return $existingUserScores->averageScores;
            } else {
                // 新增到資料庫
                DB::table('user_scores')->insert([
                    'user_id' => $user->id,
                    'averageScores' => $averageScores,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                // 回傳新增的資料
                return $averageScores;
        }
    }
}
