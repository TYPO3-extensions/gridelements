#
# Table structure for table 'tx_gridelements_backend_layout'
#
CREATE TABLE tx_gridelements_backend_layout (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage int(11) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3ver_move_id int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,
	alias varchar(255) DEFAULT '' NOT NULL,
	frame int(11) DEFAULT '0' NOT NULL,
	description text,
	horizontal tinyint(4) DEFAULT '0' NOT NULL,
	top_level_layout tinyint(4) DEFAULT '0' NOT NULL,
	config text,
	pi_flexform_ds mediumtext,
	pi_flexform_ds_file text,
	icon text,	
	
	PRIMARY KEY (uid),
	KEY parent (pid,deleted,hidden,sorting),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	colPos smallint(6) DEFAULT '0' NOT NULL,
	layout varchar(255) DEFAULT '' NOT NULL,
	backupColPos smallint(6) DEFAULT '-2' NOT NULL,
	tx_gridelements_backend_layout varchar(255) DEFAULT '' NOT NULL,
	tx_gridelements_children int(11) DEFAULT '0' NOT NULL,
	tx_gridelements_container int(11) DEFAULT '0' NOT NULL,
	tx_gridelements_columns int(11) DEFAULT '0' NOT NULL

	KEY gridelements (tx_gridelements_container,tx_gridelements_columns)
);