<?php

/**
 * Description of Advertisement
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class Advertisement extends DataObject {
	
	public static $use_js_tracking = true;
	
	public static $db = array(
		'Title'				=> 'Varchar',
		'TargetURL'			=> 'Varchar(255)',
		'LinkType' => 'Enum("Internal, External, File")',
	);
	
	public static $has_one = array(
		'InternalPage'		=> 'Page',
		'Campaign'			=> 'AdCampaign',
		'Image'				=> 'BetterImage',
	);
	
	public static $summary_fields = array('Title');
	
	public function getCMSFields() {
		$fields = new FieldSet();
		$fields->push(new TabSet('Root', new Tab('Main', 
			new TextField('Title', 'Title')
		)));
		
		if ($this->ID) {
			$impressions = $this->getImpressions();
			$clicks = $this->getClicks();

			$fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', 'Impressions', $impressions), 'Title');
			$fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', 'Clicks', $clicks), 'Title');
			
			$fields->addFieldsToTab('Root.Main', array(
				new ImageField('Image'),
				new LiteralField('Link', '<div class="field"><label>Link Target</label></div>'),
				$group = new SelectionGroup('LinkType', array(
						'Internal//Link to a page on this website' => new TreeDropdownField('InternalPageID', 'Link Target', 'SiteTree'),
						'External//Link to an external website' => new TextField('TargetURL', 'Link Target URL'),
				)),
				new HasOnePickerField($this, 'Campaign', 'Ad Campaign')
			));
		}
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
	
	protected $impressions;

	public function getImpressions() {
		if (!$this->impressions) {
			$query = new SQLQuery('COUNT(*) AS Impressions', 'AdImpression', '"ClassName" = \'AdImpression\' AND "AdID" = '.$this->ID);
			$res = $query->execute();
			$obj = $res->first();

			$this->impressions = 0;
			if ($obj) {
				$this->impressions = $obj['Impressions'];
			}
			
		}

		return $this->impressions;
	}
	
	protected $clicks;
	
	public function getClicks() {
		if (!$this->clicks) {
			$query = new SQLQuery('COUNT(*) AS Clicks', 'AdImpression', '"ClassName" = \'AdClick\' AND "AdID" = '.$this->ID);
			$res = $query->execute();
			$obj = $res->first();

			$this->clicks = 0;
			if ($obj) {
				$this->clicks = $obj['Clicks'];
			}
		}
		
		return $this->clicks;
	}
	
	public function forTemplate($width = null, $height = null) {
		$inner = Convert::raw2xml($this->Title);
		if ($this->ImageID && $this->Image()->ID) {
			if ($width) {
                $converted = $this->Image()->SetRatioSize($width, $height);
                if ($converted) {
                    $inner = $converted->forTemplate();
                }
				
			} else {
                $inner = $this->Image()->forTemplate();
			}
		}
		
		$class = '';
		if (self::$use_js_tracking) {
			$class = 'class="adlink" ';
		}
		
		$tag = '<a '.$class.' href="'.$this->Link().'" adid="'.$this->ID.'">'.$inner.'</a>';

		return $tag;
	}
	
	public function SetRatioSize($width, $height) {
		return $this->forTemplate($width, $height);
	}
	
	public function Link() {
		if (self::$use_js_tracking) {
			Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery-packed.js');
			Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');
			Requirements::javascript('advertisements/javascript/advertisements.js');

			$link = Convert::raw2att($this->InternalPageID ? $this->InternalPage()->AbsoluteLink() : $this->TargetURL);
			
		} else {
			$link = Controller::join_links(Director::baseURL(), 'adclick/go/'.$this->ID);
		}
		return $link;
	}
	
	public function getTarget() {
		return $this->InternalPageID ? $this->InternalPage()->AbsoluteLink() : $this->TargetURL;
	}
}
