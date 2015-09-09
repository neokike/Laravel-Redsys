<?php 
namespace Neokike\Redsys;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class RedsysServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		//$this->package('ubublog/ceca');
        AliasLoader::getInstance()->alias('Redsys', 'Neokike\Redsys\Facades\Redsys');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('redsys', 'Neokike\Redsys\Redsys');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
