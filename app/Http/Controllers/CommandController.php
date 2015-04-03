<?php namespace App\Http\Controllers;

use Input;
use Response;
use App\Project;
use App\Command;
use App\ServerLog;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommandRequest;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function listing($project_id, $action)
    {
        $project = Project::findOrFail($project_id);
        
        $commands = Command::where('project_id', '=', $project->id)
                           ->where('step', 'LIKE', '%' . ucfirst($action))
                           ->orderBy('order')
                           ->get();

        // fixme: there has to be a better way to do this
        // this triggers the servers to be loaded so that they exist in the model
        foreach ($commands as $command) {
            $command->servers;
        }

        $action = ucfirst($action);

        return view('commands.listing', [
            'breadcrumb' => [
                ['url' => url('projects', $project->id), 'label' => $project->name]
            ],
            'title'      => deploy_step_label($action),
            'project'    => $project,
            'action'     => $action,
            'commands'   => $commands
        ]);
    }

    public function store(StoreCommandRequest $request)
    {
        $max = Command::where('project_id', '=', Input::get('project_id'))
                      ->where('step', '=', ucwords(Input::get('step')))
                      ->orderBy('order', 'desc')
                      ->first();

        $order = 0;
        if (isset($max)) {
            $order = (int) $max->order + 1;
        }

        $command = new Command;
        $command->name       = $request->name;
        $command->user       = $request->user;
        $command->project_id = $request->project_id;
        $command->script     = $request->script;
        $command->step       = ucwords($request->step);
        $command->order      = $order;
        $command->save();

        $command->servers()->attach($request->servers);

        $command->servers; // Triggers the loading

        return $command;
    }

    public function update($command_id, StoreCommandRequest $request)
    {
        $command = Command::findOrFail($command_id);
        $command->name   = $request->name;
        $command->user   = $request->user;
        $command->script = $request->script;
        $command->save();

        $command->servers()->detach();
        $command->servers()->attach($request->servers);

        $command->servers; // Triggers the loading

        return $command;
    }

    public function destroy($command_id)
    {
        $command = Command::findOrFail($command_id);
        $command->delete();

        return Response::json([
            'success' => true
        ], 200);
    }

    public function status($log_id, $include_log = false)
    {
        $log = ServerLog::findOrFail($log_id);

        $log->started  = ($log->started_at ? $log->started_at->format('g:i:s A') : null);
        $log->finished = ($log->finished_at ? $log->finished_at->format('g:i:s A') : null);
        $log->runtime  = ($log->runtime() === false ? null : human_readable_duration($log->runtime()));
        $log->script   = '';

        if (!$include_log) {
            $log->output = ((is_null($log->output) || !strlen($log->output)) ? null : '');
        }

        return $log;
    }

    public function log($log_id)
    {
        return $this->status($log_id, true);
    }

    public function reorder()
    {
        $order = 0;

        foreach (Input::get('commands') as $command_id) {
            $command = Command::findOrFail($command_id);

            $command->order = $order;

            $command->save();

            $order++;
        }

        return Response::json([
            'success' => true
        ], 200);
    }
}
