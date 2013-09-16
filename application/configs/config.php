<?php
$translate = Zend_Registry::get('Zend_Translate');
return array(
    'filter'=>array(
        'sys_para'=>array(
            'max_distance'=>50,
            'min_distance'=>0.5
        ),
        'user_para'=>array(
            'sort_by' =>0,
            'is_match_interest' =>0,
            'is_match_location' =>0,
            'is_show_map' =>0,
            'place_lat' =>22.4,
            'place_lng' =>114.1,
            'radius' =>18,
            'rpp' => 3,
            'q'=>null,
            'user_id'=>null,
            'tag_id'=>null,
            'tag_range'=>null,
            'last_id'=>null,
            'tree'=>null,
            'is_all_time' =>1,
            'begin_date'=>null,
            'end_date'=>null
        )
    ),
	'feed'=>array(
		'sort_by'=>array('new','hot','ending')
	),
	'user_feed'=>array(
        'rpp' => 3
    ),
    'table'=>array(
        'log_action'=>array(
            'action_type' => array(
                'create'=>1,
                'update'=>2,
                'bookmark'=>3,
                'tag'=>4
            ),
            'object_type'=>array(
                'event'=>1,
                'tag'=>2,
                'user'=>3
            )
        )
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
	),/*
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
			'tree_ids'=>array('Zend_Validate'=>array('presence'=>'required'),'default'=>'|0|'),
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
			//'place_lat'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
			//'place_lng'=>array('Zend_Validate'=>array(new Zend_Validate_Float())),
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
	),*/
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
     ),
     'pics'=>array(
        'sys'=>array(
            'not_found'=> APPLICATION_PATH ."/../public/images/not-found.png",
            'loading' => APPLICATION_PATH ."/../public/images/loading_placeholder.gif"
        ),
        'user'=>array(
            'default'=> APPLICATION_PATH ."/../public/images/upload_img1.gif"//APPLICATION_PATH ."/../public/images/no-profile.gif"
        ),
        'event'=>array(
            'public'=> APPLICATION_PATH ."/../public/images/public_event.gif",
            'private'=> APPLICATION_PATH ."/../public/images/private_event.gif",
            'attraction'=> APPLICATION_PATH ."/../public/images/attraction.gif"
        ),
        'tag'=>array(
            'default'=> APPLICATION_PATH ."/../public/images/tag.jpg"
        )
     ),
     'upload_paths'=>array(
        'user'=>array(
            'profile_pic'=> APPLICATION_PATH ."/../public/uploads/user_profile_pics/"
        ),
        'event'=>array(
            'pic'=> APPLICATION_PATH ."/../public/uploads/events/"
        )
     )
);
?>