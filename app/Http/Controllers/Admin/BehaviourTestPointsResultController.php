<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BehaviourTestPointsResult;
use App\Models\BehaviourTestPointsResultRange;
use App\Models\BehaviourTestQuizGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BehaviourTestPointsResultController extends Controller
{
    public function index()
    {
        Session::put('page', 'behaviourTestPointsResult');
        $pointsResults = BehaviourTestPointsResult::with(['group', 'ranges'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return view('admin.behaviour_test_points_result.index', [
            'pointsResults' => $pointsResults,
        ]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'behaviourTestPointsResult');
        $quizGroups = BehaviourTestQuizGroup::orderBy('quiz_group_order', 'asc')->get();

        if ($request->isMethod('post')) {
            $data = $request->all();

            $validator = $this->makeValidator($data);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $rangeErrors = $this->validateRanges($data['ranges'] ?? []);
            if (!empty($rangeErrors)) {
                return redirect()->back()->withInput()->withErrors($rangeErrors);
            }

            DB::transaction(function () use ($data) {
                $group = BehaviourTestQuizGroup::findOrFail($data['behaviour_test_quiz_group_id']);
                $slug = $this->generateSlug($group->title);

                $pointsResult = BehaviourTestPointsResult::create([
                    'behaviour_test_quiz_group_id' => $data['behaviour_test_quiz_group_id'],
                    'slug' => $slug,
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ]);

                $this->syncRanges($pointsResult, $data['ranges']);
            });

            return redirect('admin/behaviour-test-points-result-index')->with('success', 'Points result inserted successfully');
        }

        return view('admin.behaviour_test_points_result.create', compact('quizGroups'));
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'behaviourTestPointsResult');
        $pointsResult = BehaviourTestPointsResult::with('ranges')->findOrFail($id);
        $pointsResultData = $pointsResult->toArray();
        $quizGroups = BehaviourTestQuizGroup::orderBy('quiz_group_order', 'asc')->get();

        if ($request->isMethod('post')) {
            $data = $request->all();

            $validator = $this->makeValidator($data);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $rangeErrors = $this->validateRanges($data['ranges'] ?? []);
            if (!empty($rangeErrors)) {
                return redirect()->back()->withInput()->withErrors($rangeErrors);
            }

            DB::transaction(function () use ($data, $pointsResult) {
                $group = BehaviourTestQuizGroup::findOrFail($data['behaviour_test_quiz_group_id']);
                $slug = $this->generateSlug($group->title, $pointsResult->id);

                $pointsResult->update([
                    'behaviour_test_quiz_group_id' => $data['behaviour_test_quiz_group_id'],
                    'slug' => $slug,
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ]);

                $pointsResult->ranges()->delete();
                $this->syncRanges($pointsResult, $data['ranges']);
            });

            return redirect('admin/behaviour-test-points-result-index')->with('success', 'Points result updated successfully');
        }

        return view('admin.behaviour_test_points_result.edit', compact('pointsResult', 'pointsResultData', 'quizGroups'));
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'behaviourTestPointsResult');
        BehaviourTestPointsResult::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);

        return redirect()->back()->with('success', 'Points result deleted successfully');
    }

    private function makeValidator(array $data)
    {
        $rules = [
            'behaviour_test_quiz_group_id' => ['required', 'integer', 'exists:behaviour_test_quiz_groups,id'],
            'ranges' => ['required', 'array', 'min:1'],
            'ranges.*.range_start' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.range_end' => ['required', 'integer', 'min:0', 'max:100'],
            'ranges.*.result_text' => ['required', 'string'],
        ];

        $messages = [
            'behaviour_test_quiz_group_id.required' => 'Please select a quiz group.',
            'ranges.required' => 'Please add at least one result range.',
            'ranges.min' => 'Please add at least one result range.',
            'ranges.*.range_start.required' => 'Please enter the start of the range.',
            'ranges.*.range_end.required' => 'Please enter the end of the range.',
            'ranges.*.result_text.required' => 'Please enter the result text.',
        ];

        return Validator::make($data, $rules, $messages);
    }

    private function validateRanges(array $ranges): array
    {
        $errors = [];
        $filteredRanges = [];

        foreach ($ranges as $range) {
            if (
                isset($range['range_start'], $range['range_end'], $range['result_text']) &&
                $range['result_text'] !== ''
            ) {
                $filteredRanges[] = [
                    'range_start' => (int) $range['range_start'],
                    'range_end' => (int) $range['range_end'],
                    'result_text' => trim($range['result_text']),
                ];
            }
        }

        if (empty($filteredRanges)) {
            return ['ranges' => 'Please add at least one result range.'];
        }

        foreach ($filteredRanges as $index => $range) {
            $row = $index + 1;

            if ($range['range_start'] > $range['range_end']) {
                $errors["ranges.$index.range_end"] = "Row $row: end range must be greater than or equal to start range.";
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        if ($filteredRanges[0]['range_start'] !== 0) {
            $errors['ranges.0.range_start'] = 'The first range must start at 0%.';
        }

        $lastIndex = count($filteredRanges) - 1;
        if ($filteredRanges[$lastIndex]['range_end'] !== 100) {
            $errors["ranges.$lastIndex.range_end"] = 'The last range must end at 100%.';
        }

        for ($i = 1; $i < count($filteredRanges); $i++) {
            $expectedStart = $filteredRanges[$i - 1]['range_end'] + 1;
            if ($filteredRanges[$i]['range_start'] !== $expectedStart) {
                $errors["ranges.$i.range_start"] = "Row " . ($i + 1) . ": start range must be {$expectedStart}% (previous end + 1).";
            }
        }

        return $errors;
    }

    private function syncRanges(BehaviourTestPointsResult $pointsResult, array $ranges): void
    {
        $order = 0;

        foreach ($ranges as $range) {
            if (
                !isset($range['range_start'], $range['range_end'], $range['result_text']) ||
                $range['result_text'] === ''
            ) {
                continue;
            }

            BehaviourTestPointsResultRange::create([
                'behaviour_test_points_result_id' => $pointsResult->id,
                'range_start' => (int) $range['range_start'],
                'range_end' => (int) $range['range_end'],
                'result_text' => trim($range['result_text']),
                'range_order' => $order++,
            ]);
        }
    }

    private function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title . '-points-result');
        $slug = $baseSlug;
        $counter = 1;

        while (
            BehaviourTestPointsResult::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
