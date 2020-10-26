<?php declare(strict_types=1);

/**
* @package   s9e\imgsize
* @copyright Copyright (c) 2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\imgsize;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return ['core.text_formatter_s9e_configure_after' => 'onConfigure'];
	}

	public function onConfigure($event)
	{
		$configurator = $event['configurator'];
		if (!isset($configurator->BBCodes['IMG'], $configurator->tags['IMG']))
		{
			return;
		}

		// Declare the height and width attributes
		$tag = $configurator->tags['IMG'];
		foreach (['height', 'width'] as $attrName)
		{
			if (isset($tag->attributes[$attrName]))
			{
				continue;
			}

			$attribute = $tag->attributes->add($attrName);
			$attribute->filterChain->append('#uint');
			$attribute->required = false;
		}

		// Reparse the default attribute's value as a pair of dimensions
		$configurator->BBCodes['IMG']->defaultAttribute = 'dimensions';
		$tag->attributePreprocessors->add(
			$configurator->BBCodes['IMG']->defaultAttribute,
			'/^(?<width>\\d+),(?<height>\\d+)/'
		);

		// Preserve the ability to use the default attribute to specify the URL
		$tag->attributePreprocessors->add(
			$configurator->BBCodes['IMG']->defaultAttribute,
			'/^(?!\\d+,\\d+)(?<src>.*)/'
		);

		// Update the template
		$dom = $tag->template->asDOM();
		foreach ($dom->query('//img') as $img)
		{
			$img->prependXslCopyOf('@width');
			$img->prependXslCopyOf('@height');
		}
		$dom->saveChanges();
	}
}