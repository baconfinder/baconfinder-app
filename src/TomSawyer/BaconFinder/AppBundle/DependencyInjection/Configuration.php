<?php

namespace TomSawyer\BaconFinder\AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tom_sawyer_bacon_finder_app');

        $rootNode->children()
            ->scalarNode('fb_app_id')->isRequired()->end()
            ->scalarNode('fb_app_secret')->isRequired()->end()
            ->scalarNode('twitter_app_id')->isRequired()->end()
            ->scalarNode('twitter_app_secret')->isRequired()->end()
            ->scalarNode('twitter_app_token')->isRequired()->end()
            ->scalarNode('twitter_app_token_secret')->isRequired()->end()
            ->integerNode('import_frequency')->isRequired()->end()
            ->scalarNode('user_class')->defaultValue('TomSawyer\BaconFinder\AppBundle\Model\User')->end()
            ->end();

        return $treeBuilder;
    }
}
