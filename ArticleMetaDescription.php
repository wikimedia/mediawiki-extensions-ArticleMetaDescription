<?php
/**
 * ArticleMetaDescription - adds meta-description tag containing snippet of the article
 *
 * It takes first paragraph of the article and put into <meta description="..." /> tag inside
 * page header section. All templates (infoboxes, tables etc.) are ignored by default. It's
 * possible to set predefined description for main page (configured in MediaWiki:Mainpage) by
 * putting desired text into MediaWiki:Description message.
 *
 * @file
 * @ingroup Extensions
 * @version 1.3.1
 * @date 2 December 2019
 * @author Adrian 'ADi' Wieczorek <adi@wikia-inc.com>
 * @link https://www.mediawiki.org/wiki/Extension:ArticleMetaDescription Documentation
 * @license GPL-2.0-or-later
 */

class ArticleMetaDescription {

	/**
	 * @param OutputPage &$out
	 * @param string &$text
	 */
	public static function onOutputPageBeforeHTML( &$out, &$text ) {
		$message = null;
		$titleObj = $out->getTitle();

		if ( $titleObj->isMainPage() ) {
			// we're on Main Page, check MediaWiki:Description message
			$message = wfMessage( 'description' )->inContentLanguage()->text();
		}

		if ( ( $message == null ) || wfMessage( 'description' )->inContentLanguage()->isDisabled() ) {
			$tmp = preg_replace( '/<table[^>]*>.*<\/table>/siU', '', $text );
			$tmp = preg_replace( '/<div[^>]*>.*<\/div>/siU', '', $tmp );
			$tmp = preg_replace( '/<style[^>]*>.*<\/style>/siU', '', $tmp );
			$tmp = preg_replace( '/<script[^>]*>.*<\/script>/siU', '', $tmp );
			$tmp = preg_replace( '/\n|\t/', ' ', $tmp );
			// @todo FIXME: per discussion with bawolff in December 2019, this should
			// use Sanitizer::stripAllTags instead
			$tmp = strip_tags( $tmp, '<p>' );

			$matches = null;
			preg_match_all( '/<p>(.*)<\/p>/siU', $tmp, $matches );
			if ( count( $matches ) ) {
				$paragraphs = $matches[1];
				$description = '';
				foreach ( $paragraphs as $paragraph ) {
					$paragraph = trim( $paragraph );
					if ( !empty( $paragraph ) ) {
						$description = $paragraph;
						break;
					}
				}
				if ( !empty( $description ) ) {
					// FILTHY HACK - clean up Cite citation numbers from the output
					// e.g. if our $description looks like this before:
					// 'This is a sentence.[9]', the numbers would then show up in Google
					// search results, which we obviously don't want. So just regex it out...
					// @todo Should probably be active only when Cite is? (A fair amount of
					// wikis have Cite installed, though, which is why no such check exists
					// here currently.
					$description = preg_replace(
						// 1st part matches numbers like [1]
						// 2nd part matches HTML-encoded versions, like &amp;#91;1&amp;#93;
						// 3rd is the one that actually works (thanks, bawolff!)
						// <bawolff> ashley: maybe your regex is happening before double encoding, but after single
						'/(\[\d{0,}\]|\&amp;\#91;\d{0,}\&amp;\#93\;|\&\#91;\d*\&\#93;)/',
						'',
						$description
					);
				}
			}
		} else {
			// MediaWiki:Description message found, use it
			$description = $message;
		}

		if ( !empty( $description ) ) {
			$out->addMeta( 'description', $description );
		}
	}
}
