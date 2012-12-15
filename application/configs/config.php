<?php
$translate = Zend_Registry::get('Zend_Translate');
return array(
    'filter'=>array(
        'max_distance'=>50,
        'min_distance'=>0.5,
        'user_para'=>array(
            'sort_by' =>0,
            'is_match_interest' =>0,
            'is_match_location' =>0,
            'is_show_map' =>0,
            'lat' =>22.4,
            'lng' =>114.1,
            'radius' =>18,
            'all_time' =>1,
        )
    ),
	'feed'=>array(
		'sort_by'=>array('new','hot','ending')
	),
	'feed_item'=>array(
		'info_line'=>array(
			'basic'=>array(
				'begin_datetime',
				'end_datetime',
				'place',
				'price',
				'like_count'
			),
			'additional'=>array(
				'organiser_name',
				'organiser_email',
				'organiser_website',
				'organiser_tel',
				'application_place',
				'application_begin_datetime',
				'application_end_datetime'
			)
		)
	),
	'post'=>array(
		'adv_submit'=>array(
			'name'=>array(
				'Zend_Validate'=>array(
					'presence'=>'required',
					new Zend_Validate_StringLength(
						array('min' => 4)
					)
				)
			),
			//'slug_name'=>array(),
			'type'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>'event'),
			'mode'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>'advanced'),
			'status'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>1),
			//'submitter_id'=>array('Zend_Validate'=>array('presence'=>'required')),
			'category_ids'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>'|0|'),
			'begin_datetime'=>array(
				//'Zend_Validate'=>array('presence'=>'required'),
				'Custom_Validate'=>array('func'=>'Validate_DateTimeRange','para'=>array('begin_datetime','end_datetime'))
			),
			'end_datetime'=>array(
				//'Zend_Validate'=>array('presence'=>'required'),
			),
			'score'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>0),
			'bookmark_user_cnt'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>0),
			'all_day'=>array('Zend_Validate'=>array('int')),
			'not_time_specific'=>array('Zend_Validate'=>array('int')),
			'description'=>array(),
			//'lat'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			//'lng'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			'zoom'=>array('Zend_Validate'=>array('int')),
			//'application_lat'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			//'application_lng'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			'application_zoom'=>array('Zend_Validate'=>array('int')),
			'application_begin_datetime'=>array(
				'Custom_Validate'=>array('func'=>'Validate_DateTimeRange','para'=>array('application_begin_datetime','application_end_datetime'))
			),
			'application_end_datetime'=>array(),
			'price'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			'price_note'=>array(),
			'organiser_name'=>array(),
			'organiser_tel'=>array(),
			'organiser_email'=>array('Zend_Validate'=>array(new Zend_Validate_EmailAddress())),
			'organiser_website'=>array('Zend_Validate'=>array(new Zend_Validate_Callback(array('Zend_Uri', 'check')))),
			//'create_time'=>array('Zend_Validate'=>array('presence'=>'required')),
		)
	),
    'email_templates'=>array(
        'user_signup_confirmation' =>array(
            'subject' => $translate->translate('Account confirmation from Tagbees')
        ),
        'user_signup_welcome' =>array(
            'subject' => $translate->translate('Welcome to Tagbees!')
        ),
        'user_to_reset_password' =>array(
            'subject' => $translate->translate('Reset password on Tagbees')
        )
    ),
    'languages'=>array(
        'zh-hk'=>'繁體中文',
        'en'=>'English'
     )
     /*deprecated ,
	'category'=>array(
		'meals'=>array(
				'chinese dishes'=>array('guangdong', 'sichuan'),
				'japanese dishes'=>array(),
				'french dishes',
				'italy dishes',
				'desserts'
			),
		'activities'=>array(
				'movies',
				'karaoke',
				'sports',
				'fitness',
				'petkeeping',
				'tutoring',
				'traveling',
			),
		'fashion'=>array(
				'clothing',
				'shoes',
				'handbags',
				'makeups'
			),
		'electronics'=>array(
				'computers',
				'mobiles',
				'cameras'
			),
		'books'=>array(
				'textbooks',
				'comics',
				'magazines'
			),
		'games'=>array(
				'TV games',
				'computer games'
			),
		'musics'=>array(
				'chinese music',
				'western music'
			)
		)*/
);
?>