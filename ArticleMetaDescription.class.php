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
 * @version 1.3.0
 * @date 25 August 2019
 * @author Adrian 'ADi' Wieczorek <adi@wikia-inc.com>
 * @link https://www.mediawiki.org/wiki/Extension:ArticleMetaDescription Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class ArticleMetaDescription {

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
						'/\[\d{0,}\]/',
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