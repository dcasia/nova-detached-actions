<?php

namespace DigitalCreative\DetachedActions;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\DispatchAction as BaseDispatchAction;
use Laravel\Nova\Actions\Transaction;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Nova;
use Throwable;

class DispatchAction extends BaseDispatchAction
{
    /**
     * Dispatch the given action.
     *
     * @param ActionRequest $request
     * @param Action $action
     * @param string $method
     * @param Collection $models
     * @param ActionFields $fields
     *
     * @throws Throwable
     * @return mixed
     */
    public static function forModels(
        ActionRequest $request,
        Action $action,
        $method,
        Collection $models,
        ActionFields $fields
    )
    {
        if ($action instanceof ShouldQueue) {
            return static::queueForModels($request, $action, $method, $models);
        }

        return Transaction::run(function ($batchId) use ($fields, $request, $action, $method, $models) {
            if (!$action->withoutActionEvents) {
                Nova::actionEvent()->createForModels($request, $action, $batchId, $models);
            }

            return $action->withBatchId($batchId)->{$method}($fields, $models);
        }, function ($batchId) {
            Nova::actionEvent()->markBatchAsFinished($batchId);
        });
    }
}
