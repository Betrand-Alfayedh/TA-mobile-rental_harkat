<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PakaiSupirLabel extends Component
{
    /**
     * Create a new component instance.
     */
    public $pakaiSupir;
    public $supir;
    public function __construct($pakaiSupir, $supir = null)
    {
        $this->pakaiSupir = $pakaiSupir;
        $this->supir = $supir;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.pakai-supir-label');
    }
}
