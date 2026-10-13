<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserLevel;
use Illuminate\Http\Request;
use App\Models\TaskMilestone;
use App\Models\UserLevelTask;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class LevelController extends Controller
{
    public function levelIndex()
    {
        Session::put('page', 'user-level-task');
        $this->setLevelPositionAfterDelete();
        $userLevel = UserLevel::orderBy('level', 'asc')->with('userLevelTasks')->get()->toArray();
        return view('admin.user_level.level_task.index', ['userLevel' => $userLevel]);
    }

    public function levelCreate(Request $request)
    {
        Session::put('page', 'user-level-task');
        $lastLevel = UserLevel::max('level') ?? 0;
        $nextLevel = $lastLevel + 1;
        if ($request->isMethod('POST')) {
            $data = $request->except('_token');
            $validator = Validator::make($request->all(), [
                "title" => "required|unique:user_levels,title",
                "improvement" => "required|numeric|max:100",
                "description" => "required",
                "video_title" => "required",
                "video_description" => "required",
                "video_link" => "required|url",
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            DB::transaction(function () use ($data) {
                // Insert user level first
                UserLevel::with('userLevelTasks');
                $userLevel = UserLevel::create([
                    'level'=>$data['level'],
                    'title'=>$data['title'],
                    'improvement_per'=>$data['improvement'],
                    'description'=>$data['description'],
                    'video_title'=>$data['video_title'],
                    'video_description'=>$data['video_description'],
                    'video_url'=>$data['video_link'],
                ]);
                // Insert related tasks
                foreach ($data['tasks'] as $task) {
                    UserLevelTask::create([
                        'user_level_id'=>$userLevel->id,
                        'task_type'=>$task['task_type'],
                        'total_count'=>$task['total_count'],
                        'duration'=>$task['duration'],
                    ]);
                }
            });
            return redirect()->to('admin/user-level-index')->with('success', "new level is added successfully");
        }
        return view('admin.user_level.level_task.create', [
            'nextLevel' => $nextLevel
        ]);
    }
    public function levelEdit(Request $request, $id)
    {
        $userLevel= UserLevel::find($id);
        $userLevelTask = UserLevelTask::where('user_level_id', $id)->get()->toArray();
        // dd($userLevelTask);
        if ($request->isMethod('POST')) {
            $data=$request->except('_token');
               $validator = Validator::make($request->all(), [

                    "title" => "required",
                    "improvement" => "required|numeric|max:100",
                    "description" => "required",
                    "video_title" => "required",
                    "video_description" => "required",
                    "video_link" => "required|url",

                ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            $userLevel->level=$data['level'];
            $userLevel->title=$data['title'];
            $userLevel->improvement_per=$data['improvement'];
            $userLevel->description=$data['description'];
            $userLevel->video_title=$data['video_title'];
            $userLevel->video_description=$data['video_description'];
            $userLevel->video_url=$data['video_link'];
            if ($userLevel->save()) {
                // Collect all existing task IDs for this user level
                $existingTaskIds = UserLevelTask::where('user_level_id', $userLevel->id)->pluck('id')->toArray();
                $submittedTaskIds = [];

                // Update or create tasks, and collect submitted IDs
                foreach ($data['tasks'] as $task) {
                    if (!empty($task['taskID'])) {
                        $UserLevelTaskUpdate = UserLevelTask::find($task['taskID']);
                        if ($UserLevelTaskUpdate) {
                            $UserLevelTaskUpdate->task_type = $task['task_type'];
                            $UserLevelTaskUpdate->total_count = $task['total_count'];
                            $UserLevelTaskUpdate->duration = $task['duration'];
                            $UserLevelTaskUpdate->save();
                            $submittedTaskIds[] = $task['taskID'];
                        }
                    } else {
                        $newTask = UserLevelTask::create([
                            'user_level_id' => $userLevel->id,
                            'task_type' => $task['task_type'],
                            'total_count' => $task['total_count'],
                            'duration' => $task['duration'],
                        ]);
                        $submittedTaskIds[] = $newTask->id;
                    }
                }

                // Delete tasks that exist in DB but not in the submitted data
                $tasksToDelete = array_diff($existingTaskIds, $submittedTaskIds);
                if (!empty($tasksToDelete)) {
                    UserLevelTask::whereIn('id', $tasksToDelete)->delete();
                }
                 return redirect()->to('admin/user-level-index')->with('success', "level updated successfully");
            }
        }
        return view('admin.user_level.level_task.edit', ['userLevel'=>$userLevel, 'userLevelTask'=>$userLevelTask  ]);
    }

    public function levelDestroy(string $id)
    {
        $deleteUserLevel=UserLevel::destroy($id);
        if ($deleteUserLevel) {
            $this->setLevelPositionAfterDelete();
            return redirect()->to('admin/user-level-index')->with('success', "level deleted successfully");
        }
    }
// after rearranging row
    public function setLevelPosition(Request $request)
    {
        $positions = UserLevel::all();
        foreach ($positions as $position) {
            foreach ($request->order as $order) {
                if ($order['id'] == $position->id) {
                    $position->update(['level' => $order['position']]);
                }
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Update Successfully.'
        ], 200);
    }
    //set Level position after deleting
    public function setLevelPositionAfterDelete()
    {
        $allLevel= UserLevel::orderBy('level', 'asc')->get();
        $levelIndex=1;
        foreach ($allLevel as $level) {
            $level->update(['level'=>$levelIndex]);
            $levelIndex++;
        }
    }
    public function milestoneIndex()
    {
        Session::put('page', 'user-task-milestone');
        $taskMilestone = TaskMilestone::get()->toArray();
        return view('admin.user_level.milestone_task.index', ['taskMilestone' => $taskMilestone]);
    }

    public function milestoneCreate(Request $request)
    {
        Session::put('page', 'user-task-milestone');

        if ($request->isMethod('POST')) {
            $data=$request->except('_token');
            $validator = Validator::make($request->all(), [
                "milestone_type" =>"required",
                "milestone_title" =>"required",
                "milestone_description" =>"required",
                "milestone_count" =>"required",
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            $taskmilestone= new TaskMilestone();
            $taskmilestone->create($data);
            return redirect()->to('admin/task-milestone-index')->with('success', 'milestone added successfully');
        }
        return view('admin.user_level.milestone_task.create');
    }

    public function milestoneEdit(Request $request, $id)
    {
        $taskMiletone=TaskMilestone::find($id);
        if ($request->isMethod('POST')) {
            $data=$request->except('_token');
            $validator = Validator::make($request->all(), [
                "milestone_title" =>"required",
                "milestone_description" =>"required",
                "milestone_count" =>"required",
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            TaskMilestone::find($id)->update($data);
            return redirect()->to('admin/task-milestone-index')->with('success', 'milestone update successfully');
        }
         return view('admin.user_level.milestone_task.edit', ['taskMilestone'=>$taskMiletone]);
    }

    public function milestoneDestroy(string $id)
    {
        TaskMilestone::destroy($id);
            return redirect()->to('admin/task-milestone-index');
    }
}
