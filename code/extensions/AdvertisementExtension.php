<?php

/**
 * Description of AdvertisementExtension
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdvertisementExtension extends DataObjectDecorator {

	static $allow_inherit = true;
	static $allow_multiple = true;

	public function extraStatics() {
		return array(
			'db'			=> array(
				'UseRandom'			=> 'Boolean',
				'NumberOfAds'		=> 'Int',
				'InheritSettings'	=> 'Boolean',
			),
			'defaults'		=> array(
				'InheritSettings'	=> true
			),
			'many_many'		=> array(
				'Advertisements'			=> 'Advertisement',
			),
			'has_one'		=> array(
				'UseCampaign'				=> 'AdCampaign',
			)
		);
	}
	
	public function updateCMSFields(FieldSet &$fields) {
		parent::updateCMSFields($fields);
		if( self::$allow_inherit ) {
			$fields->addFieldToTab('Root.Advertisements', new CheckboxField('InheritSettings', _t('Advertisements.INHERIT', 'Inherit parent settings')));
		}
//		$fields->addFieldToTab('Root.Advertisements', new CheckboxField('UseRandom', _t('Advertisements.USE_RANDOM', 'Use random selection')));
		if( self::$allow_multiple ) {
			$fields->addFieldToTab('Root.Advertisements', new NumericField('NumberOfAds', _t('Advertisements.NUM_ADS', 'How many Ads should be returned?')));
		}
		$fields->addFieldToTab('Root.Advertisements', new ManyManyPickerField($this->owner, 'Advertisements'));
		$fields->addFieldToTab('Root.Advertisements', new HasOnePickerField($this->owner, 'UseCampaign', 'Ad Campaigns'));
	}
	
	public function AdList() {
		$toUse = $this->owner;

		if( $toUse->UseCampaignID ) {
			$toUse = $toUse->UseCampaign();
		}
		else if( $toUse->InheritSettings ) {
			while( $toUse->ParentID && $toUse->InheritSettings ) {
				$toUse = $toUse->Parent();
			}
		}

		$limit = ($this->owner->NumberOfAds ? $this->owner->NumberOfAds : '');
		return $toUse->Advertisements('', 'RAND()', '', $limit);
	}
}
