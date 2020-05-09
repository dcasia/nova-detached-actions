<?php

namespace DigitalCreative\DetachedActions;

use Illuminate\Http\Resources\PotentiallyMissing;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Exceptions\MissingActionHandlerException;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Nova;
use Throwable;

abstract class DetachedAction extends Action implements PotentiallyMissing
{
    /**
     * Indicates if this action is only available on the resource index view.
     *
     * @var bool
     */
    public $onlyOnIndex = true;

    /**
     * The displayable label of the button.
     *
     * @var string
     */
    public $label;

    /**
     * Get the displayable label of the button.
     *
     * @return string
     */
    public function label()
    {
        return $this->label ?: Nova::humanize($this);
    }

    /**
     * Execute the action for the given request.
     *
     * @param ActionRequest $request
     *
     * @throws MissingActionHandlerException|Throwable
     * @return mixed
     */
    public function handleRequest(ActionRequest $request)
    {
        $method = ActionMethod::determine($this, $request->targetModel());

        if (!method_exists($this, $method)) {
            throw MissingActionHandlerException::make($this, $method);
        }

        $fields = $request->resolveFields();

        $results = DispatchAction::forModels(
            $request, $this, $method, collect([]), $fields
        );

        return $this->handleResult($fields, [ $results ]);
    }

    /**
     * Prepare the action for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge([
            'detachedAction' => true,
            'label' => $this->label(),
        ], parent::jsonSerialize(), $this->meta());
    }

    /**
     * Determine if the object should be considered "missing".
     *
     * @return bool
     */
    public function isMissing()
    {
        return (bool) request()->input('isDetachedAction') !== true;
    }
}
