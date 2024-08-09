<?php
namespace Hitexis\PrintCalculator\Repositories;
use Webkul\Core\Eloquent\Repository;
use Illuminate\Container\Container;
use Hitexis\PrintCalculator\Models\PrintManipulation;

class PrintManipulationRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return PrintManipulation::class;
    }
}
