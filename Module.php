<?php

namespace SaSsiWidget;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface {

	public function getAutoloaderConfig() {
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}

	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}

	public function getServiceConfig() {
		return array(
			'factories' => array(
				'SaSsiWidget-ModuleOptions' => function ($sm) {
					$config = $sm->get('Configuration');

					return new Options\ModuleOptions(
							isset($config['sa-ssi-widget']) ? $config['sa-ssi-widget'] : array()
					);
				},
				'SaSsiWidget\View\Renderer\SsiRenderer' => function ($sm) {
					$renderer = new View\Renderer\SsiRenderer();
					$renderer->setHelperPluginManager($sm->get('ViewHelperManager'));
					$renderer->setResolver($sm->get('ViewResolver'));

					return $renderer;
				},
				'SaSsiWidget\View\Strategy\SsiStrategy' => function ($sm) {
					return new View\Strategy\SsiStrategy($sm->get('SaSsiWidget\View\Renderer\SsiRenderer'));
				},
			),
		);
	}

	public function getControllerPluginConfig() {
		return array(
			'factories' => array(
				'ssiWidget' => function ($sm) {
					$moduleOptions = $sm->getServiceLocator()->get('SaSsiWidget-ModuleOptions');
					$plugin = new Mvc\Controller\Plugin\SsiWidget();
					$plugin->setOptions($moduleOptions);

					return $plugin;
				},
			),
		);
	}

	public function onBootstrap(MvcEvent $e) {
		$app = $e->getApplication();
		$serviceManager = $app->getServiceManager();

		$controllerPluginBroker = $serviceManager->get('ControllerPluginManager');
		$ssiWidgetPlugin = $controllerPluginBroker->get('ssiWidget');
		$ssiWidgetPlugin->getEventManager()->attach($serviceManager->get('RouteListener'));

		//TODO: Can this be obtained from SM?
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($ssiWidgetPlugin->getEventManager());

		$ssiWidgetPlugin->setSurrogateCapability(true);
		$ssiViewStrategy = $serviceManager->get('SaSsiWidget\View\Strategy\SsiStrategy');
		$ssiViewStrategy->setSurrogateCapability(true);
	}

}
