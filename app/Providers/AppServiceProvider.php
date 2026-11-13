<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::defaultView('partials.pager');
        Paginator::defaultSimpleView('partials.pager');
        Blade::directive('zone', fn ($e) => "<?php echo \\App\\Support\\Ui::zone($e); ?>");
        Blade::directive('status', fn ($e) => "<?php echo \\App\\Support\\Ui::status($e); ?>");
        Blade::directive('qty', fn ($e) => "<?php echo \\App\\Support\\Wms::fmt($e); ?>");
        Blade::directive('d', fn ($e) => "<?php echo \\App\\Support\\Ui::date($e); ?>");
        Blade::directive('dt', fn ($e) => "<?php echo \\App\\Support\\Ui::date($e, true); ?>");
    }
}
