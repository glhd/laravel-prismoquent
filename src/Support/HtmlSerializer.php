<?php

namespace Galahad\Prismoquent\Support;

use Prismic\LinkResolver;

class HtmlSerializer
{
	/**
	 * @var \Prismic\LinkResolver
	 */
	protected $resolver;
	
	public function __construct(LinkResolver $resolver)
	{
		$this->resolver = $resolver;
	}
	
	public function __invoke($element, $content)
	{
		$nodeName = 'span';
		$attributes = [];
		$classNames = ['prismic-element'];
		
		// Blocks
		switch ($element->type) {
			// Headings
			case 'heading1':
			case 'heading2':
			case 'heading3':
			case 'heading4':
			case 'heading5':
			case 'heading6':
				$nodeName = 'h'.substr($element->type, -1);
				$content = nl2br($content);
				break;
			
			case 'paragraph':
				$nodeName = 'p';
				$content = nl2br($content);
				break;
			
			case 'preformatted':
				$nodeName = 'pre';
				$content = nl2br($content);
				break;
			
			case 'list-item':
			case 'o-list-item':
				$nodeName = 'li';
				$content = nl2br($content);
				break;
			
			case 'image':
				$nodeName = 'div';
				$classNames[] = 'prismic-img';
				$content = '<img src="'.$element->url.'" alt="'.htmlentities($element->alt).'">'; // TODO: Dimensions
				break;
			
			case 'embed':
				if (!$element->oembed->html) {
					return null;
				}
				
				$nodeName = 'div';
				$classNames[] = 'prismic-embed';
				if ($element->oembed->provider_name) {
					$attributes['data-oembed-provider'] = strtolower($element->oembed->provider_name);
				}
				
				$attributes['data-oembed'] = $element->oembed->embed_url;
				$attributes['data-oembed-type'] = strtolower($element->oembed->type);
				$content = $element->oembed->html;
				break;
		}
		
		// Spans
		switch ($element->type) {
			case 'strong':
				$nodeName = 'strong';
				break;
			
			case 'em':
				$nodeName = 'em';
				break;
			
			case 'hyperlink':
				$nodeName = 'a';
				if (isset($element->data->target)) {
					$attributes['target'] = $element->data->target;
					$attributes['rel'] = 'noopener';
				}
				
				$attributes['href'] = 'Document' === $element->data->link_type
					? $this->resolver->resolve($element->data)
					: $element->data->url;
				
				if (null === $attributes['href']) {
					// We have no link (LinkResolver said it is not valid,
					// or something else went wrong). Abort this span.
					return $content;
				}
				break;
		}
		
		if (isset($element->label)) {
			$classNames[] = "prismic-{$element->label}";
		}
		
		if (isset($element->data->label)) {
			$classNames[] = "prismic-{$element->data->label}";
		}
		
		$html = "<{$nodeName}";
		
		foreach ($attributes as $key => $value) {
			$html .= ' '.$key.'="'.htmlentities($value).'"';
		}
		
		$html .= ">{$content}</{$nodeName}>";
		
		return $html;
	}
}
