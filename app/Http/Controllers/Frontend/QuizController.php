<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FormGoal;
use App\Models\FormQuestion;
use App\Models\FormTitle;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Darryldecode\Cart\Facades\CartFacade;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Traits\ScoreSync;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;





class QuizController extends Controller
{
    use ScoreSync;
    public function index(): View
    {
        // 取得所有 FormGoal 的資料
        $formGoals = FormGoal::all();

        // 檢查是否有取得資料
        if ($formGoals->isEmpty()) {
            abort(404, '沒有找到相關的保健目標！');
        }

        // 將 FormGoal 的資料傳遞給視圖
        return view('frontend.test.quiz', [
            'formGoals' => $formGoals,
        ]);
    }

    //顯示問題頁面
    public function showQuestionsView(Request $request)
    {
        // 取得所選的保健目標
        $selectedGoals = $request->input('goals');

        //如果沒有選擇目標就回到首頁
        if (empty($selectedGoals)) {
            return redirect()->route('home.index');
        }

        // 將資料傳遞給視圖
        return view('frontend.test.questions', [
            'selectedGoals' => $selectedGoals,
        ]);
    }

    public function showQuestions(Request $request)
    {
        // 取得所選的保健目標
        $selectedGoals = $request->input('goals');

        // 取得目前第幾題
        $currentQuestionIndex = $request->input('currentQuestionIndex', 0);

        // 取得所有 FormTitle 資料，並按照 ID 排序
        $formTitles = FormTitle::orderBy('id')->get();
        $titleNames = $formTitles->pluck('title_name')->toArray();


        // 檢查是否有選擇目標
        if (empty($selectedGoals)) {
            return response()->json(['error' => '請選擇至少一個保健目標！'], 400);
        }

        // 取得相關問題
        $formQuestions = FormQuestion::whereIn('goal_id', $selectedGoals)
            ->orderBy('id') // 根據form_question_id來排序
            ->get();

        // 如果沒有問題就回傳
        if ($formQuestions->isEmpty()) {
            return response()->json(['noMoreQuestion' => true]);
        }

        // 判斷是否超過題目總數
        if ($currentQuestionIndex >= count($formQuestions)) {
            return response()->json(['noMoreQuestion' => true]);
        }
        // 把資料轉成適合js的格式
        $questionsData = $formQuestions->map(function ($question) {
            $title = $question->title; // 取得關聯的 FormTitle
            $weights = [ // 取得權重
                'weight_1' => $title ? $title->weight_1 : 1,
                'weight_2' => $title ? $title->weight_2 : 1,
                'weight_3' => $title ? $title->weight_3 : 1,
                'weight_4' => $title ? $title->weight_4 : 1,
                'weight_5' => $title ? $title->weight_5 : 1,
            ];
            // 根據權重生成動態分數
            $option1Values = $this->generateOptionValues($weights, 1, 100); // 第一個選項高分
            $option2Values = $this->generateOptionValues($weights, 2, 100); // 第二個選項中等分
            $option3Values = $this->generateOptionValues($weights, 3, 100); // 第三個選項低分
            
            return [
                'id' => $question->id,
                'question' => $question->question,
                'type' => 'radio',
                'title_id' => $title ? $title->id : null, // 取得 title_id
                'title_name' => $title ? $title->title_name : null, // 取得 title_name
                'weights' => $weights,
                'options' => [
                    [
                        'value' => $question->option1,
                        'label' => $question->option1,
                        'values' => $option1Values,
                    ],
                    [
                        'value' => $question->option2,
                        'label' => $question->option2,
                        'values' => $option2Values,
                    ],
                    [
                        'value' => $question->option3,
                        'label' => $question->option3,
                        'values' => $option3Values,
                    ],
                ],
            ];
        })->toArray();

        // 取得第一個目標的名稱
        $firstGoal = FormGoal::find($selectedGoals[0]);
        $goalName = $firstGoal ? $firstGoal->goal_name : '';

        // 判斷是否是第一道題目
        $isFirstQuestion = ($currentQuestionIndex === 0);

        // 將資料傳遞給前端
        return response()->json([
            'allQuestions' => $questionsData,
            'goalName' => $goalName,
            'currentQuestionIndex' => $currentQuestionIndex,
            'isFirstQuestion' => $isFirstQuestion,
            'titleNames' => $titleNames, // 新增：傳遞 titleNames
        ]);
    }

    // 新增處理答案的方法
    public function submitAnswers(Request $request)
    {
        $answers = $request->input('answers');
        // 初始化每個角的總加權分數
        $totalWeightedScores = [0, 0, 0, 0, 0];
        // 初始化每個角的總權重
        $questionCounts = [0, 0, 0, 0, 0];

        // 取得所有 FormTitle 資料，並按照 ID 排序
        $formTitles = FormTitle::orderBy('id')->get();
        $titleNames = $formTitles->pluck('title_name')->toArray();

        foreach ($answers as $questionId => $answerData) {
            $question = FormQuestion::find($questionId);
            if ($question) {
                $title = $question->title;
                if ($title) {
                    $weights = [
                        $title->weight_1,
                        $title->weight_2,
                        $title->weight_3,
                        $title->weight_4,
                        $title->weight_5,
                    ];
                    $values = $answerData['values'];
    
                    // 累加 values[i] * weights[i] 和 weights[i]
                    for ($i = 0; $i < 5; $i++) {
                        // $totalValueWeightProducts[$i] += $values[$i] * $weights[$i];
                        // $totalWeights[$i] += $weights[$i];
                        $totalWeightedScores[$i] += $values[$i] * $weights[$i];
                        $questionCounts[$i]++; // 累加題目數量
                    }
                }
            }
        }
    
        // 計算每個角的平均分數
        $averageScores = [];
        for ($i = 0; $i < 5; $i++) {
            // $averageScores["角" . ($i + 1)] = $totalWeights[$i] > 0 ? round($totalValueWeightProducts[$i] / $totalWeights[$i]) : 0;
            // 使用總加權分數除以題目數量來計算平均分數
            $averageScore = $questionCounts[$i] > 0 ? round($totalWeightedScores[$i] / $questionCounts[$i]) : 0;
            // 限制最大值為 100
            $averageScores[] = min($averageScore, 100); // 使用 min 函數限制最大值
        }
        return response()->json([
            'averageScores' => $averageScores,
            'titleNames' => $titleNames, // 新增：傳遞 titleNames
        ]);
    }

    // 根據權重生成選項分數的輔助方法
    private function generateOptionValues(array $weights, int $optionOrder, int $maxScore): array
    {
        $values = [];
        $totalWeight = array_sum($weights);
        if ($totalWeight == 0) {
            return [0, 0, 0, 0, 0];
        }
        // 根據選項順序調整基礎分數
        switch ($optionOrder) {
            case 1: // 第一個選項：高分
                $baseScore = $maxScore;
                break;
            case 2: // 第二個選項：中等分
                $baseScore = $maxScore * 0.6; // 例如 60%
                break;
            case 3: // 第三個選項：低分
                $baseScore = $maxScore * 0.3; // 例如 30%
                break;
            default:
                $baseScore = 0;
        }

        // 根據權重分配分數，並確保最低分數
        foreach ($weights as $weight) {
            // 計算每個角的加權分數
            $weightedScore = round(($weight / $totalWeight) * $baseScore);
            $values[] = $weightedScore;
        }
        return $values;
    }

    // ✅ **更新訪客購物車順序**
    private function updateCartOrder($id)
    {
        $order = session('cart_order', []);
        if (!in_array($id, $order)) {
            $order[] = $id;
        }
        session(['cart_order' => $order]);
    }
    public function getRecommendations(Request $request)
    {
        $titlename = $request->input('categories'); // 例如：['作息', '心理']
        $averageScores = $request->input('averageScores');
        // 找出對應的 category_id
        $formTitles_id = FormTitle::whereIn('title_name', $titlename)->pluck('id');
        $products = Product::whereIn('form_title_id', $formTitles_id)->get()->take(3);
        $quantity = 1;
    
        if (Auth::check()) {
            // 會員模式：儲存到資料庫
            $userId = Auth::id();
             // 檢查資料庫是否有資料
                $existingUserScores = DB::table('user_scores')
                    ->where('user_id',$userId)
                    ->first();
    
                if ($existingUserScores) {
                    // 更新數量
                    DB::table('user_scores')
                        ->where('id', $existingUserScores->id)
                        ->update([
                            'averageScores' => $averageScores,
                            'updated_at' => now()
                        ]);
                } else {
                    // 新增到資料庫
                    DB::table('user_scores')->insert([
                        'user_id' => $userId,
                        'averageScores' => $averageScores,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
            }
    
            foreach ($products as $product) {
                $cartItem = DB::table('shopping_carts')
                    ->where('user_id', $userId)
                    ->where('product_id', $product->id)
                    ->first();
    
                if ($cartItem) {
                    DB::table('shopping_carts')
                        ->where('user_id', $userId)
                        ->where('product_id', $product->id)
                        ->increment('quantity', $quantity);
                } else {
                    DB::table('shopping_carts')->insert([
                        'user_id' => $userId,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } else {
            // 訪客模式：使用 Session
            foreach ($products as $product) {
                // 確保 Cart::get() 返回正確的資料
                $cartItem = CartFacade::get($product->id);    
                if ($cartItem) {
                    // 如果商品已經存在，更新數量
                    CartFacade::update($product->id, ['quantity' => $quantity]);

                } else {
                    // 如果商品不存在，加入購物車
                    CartFacade::add([
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'quantity' => $quantity,
                        'attributes' => ['image' => $product->thumb_image]
                    ]);
                    $this->updateCartOrder($product->id);
                }
            }
        }
    
        return response()->json($products);
    }
    
}


