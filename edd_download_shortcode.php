<?php
/*
Plugin Name: Download Count Shortcode Easy Digital Downloads
Plugin URL: https://cleverstart.cz
Description: Přidá shortcode [download_count] který vrací počet stažení produktu.
Version: 1.2.36
Author: Pavel Janíček
Author URI: https://cleverstart.cz
*/

if (!class_exists('DownloadCount')){

  class DownloadCount{
    private static $instance = null;
    public static $alreadySet = false;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

  private function __construct() {
    add_shortcode('download_count', array( $this,'eddstazeni_shortcode'));
    add_action( 'edd_after_download_content', array( $this,'download_count_after_download_content'), 120 );
    add_filter( 'edd_settings_extensions', array( $this,'download_count_add_settings') );
    add_action( 'init', array( $this, 'set_downlad_count'));
    add_action( 'add_meta_boxes', array($this,'add_metabox') );
    add_action( 'save_post', array($this,'edddownload_save_metabox') );
    //register_deactivation_hook( array( $this, 'edd_download_count_uninstall') );
  }



  function eddstazeni_shortcode($atts = [], $content = null, $tag = ''){
    $wporg_atts = shortcode_atts([
                                       'id' => 0,
                                       'count' => 0,

                                   ], $atts, $tag);
    $download_id = ($wporg_atts['id'] == 0) ? get_the_ID() : $wporg_atts['id'];
    $download_number = edd_get_download_sales_stats( $download_id ) + $wporg_atts['count'];
    $meta_count = $this->get_meta_count($download_id);
    $download_number = $download_number + $meta_count;
    return $download_number;

  }




  	function download_count_add_settings( $settings ) {

  		$isa_eddrd_settings = array(
  			array(
  				'id' => 'edd_count_settings',
  				'name' => '<h3 class="title">Nastavení počtu stažení souboru</h3>',
  				'desc' => __( 'Settings for EDD Related Downloads Plugin.', 'easy-digital-downloads-related-downloads'),
  				'type' => 'header'
  			),
  			array(
  				'id' => 'edd_count_text',
  				'name' => 'Text k zobrazení:',
  				'desc' => 'Počet zakoupení produktu se automaticky vypisuje pod popisem produktu. Do pole výše můžete zadat vlastní text k zobrazení. <br />Text je třeba zadat ve formátu "Text před počtem stažení [download_count] text za počtem stažení" (např. "Staženo [download_count]x" pro zobrazení "Staženo 33x")<br />
  <br />
  Shortcode [download_count] ve větě vypisuje počet stažení downloadu.<br />
  <br />
  Pokud necháte pole výše prázdné, zobrazí se výchozí hodnota "Tento produkt si stáhlo již [download_count] lidí."<br />
  <br />
  Shortcode umožňuje nastavit výchozí hodnotu počtu zakoupení produktu pomocí parametru "count". Např. shortcode [download_count count=100] přidá všem downloadům 100 stažení k reálně zakoupeným produktům. <br />
  <br />
  Shortcody lze použít i samostatně. Na stránce downloadu stačí vložit shortcode [download_count] (případně [download_count count=100] pro navýšení počtu) - shortcode se automaticky spáruje s aktuálním downloadem.<br />
  <br />
  Pro použití v příspěvcích nebo na stránkách použijte shortcode [download_count id=25] (25 nahraďte id příslušného downloadu), případně [download_count id=25 count=100] pro navýšení počtu.',
  				'type' => 'rich_editor',
          'std'  => '<strong>Tento produkt si stáhlo již: [download_count] lidí.</strong>',
  			),

  			array(
  				'id' => 'edd_count_disable',
  				'name' => 'Zakázat zobrazení počtu stažení v downloadech:',
  				'desc' => 'Zakáže výchozí zobrazování na konci downloadu. Zaškrtněte, pokud chcete počet zobrazovat pouze přes shortcode (například jenom v postranním panelu)',
  				'type' => 'checkbox'
  			),

  		);

  		/* Merge plugin settings with original EDD settings */
  		return array_merge( $settings, $isa_eddrd_settings );
  	}


    function download_count_after_download_content(){
      global $post, $data, $edd_options;
        $go = isset( $edd_options['edd_count_disable'] ) ? '' : 'go';
        $sentence = $this->download_count_default_text();
        if($go){  ?>
          <span class="download_count">
          <?php echo $sentence; ?>
        </span>
        <?php
        }
    }

    function download_count_default_text(){
      $default_text_before = "<strong>Tento produkt si stáhlo již: [download_count] lidí.</strong>";
      $message = edd_get_option( 'edd_count_text', false );
      $message = ! empty( $message ) ? $message : $default_text_before;
      return do_shortcode($message);
    }


  function set_downlad_count(){
    global $edd_options;
    if(get_option('download_text_set') != 'completed'){
      $edd_options['edd_count_text'] = '<strong>Tento produkt si stáhlo již: [download_count] lidí.</strong>';
    }
    update_option('download_text_set','completed');
  }

  function edd_download_count_uninstall(){
  	delete_option("download_text_set");
  }

  public function edddownload_render_metabox() {
		global $post;
		$id = "edddownload";
		echo '<p>' . __( 'Přičtení počtu nákupů k tomuto downloadu', 'edddownload' ) . '</p>';


    echo "<input type=\"text\" id=\"edd-downloadcount\" class=\"widefat\" name=\"_edd_edddownload\" value=\"";
    echo $this->get_meta_count($post->ID);
		echo "\">";
	}


  public function add_metabox(){
      if ( current_user_can( 'edit_product', get_the_ID() ) ) {
        add_meta_box( 'edd_downloadcount', 'Přičtení počtu stažení', array($this,'edddownload_render_metabox') , 'download', 'side' );
      }
    }

  public function edddownload_save_metabox( $post_id ) {


    if (array_key_exists('_edd_edddownload', $_POST)) {
        update_post_meta(
            $post_id,
            '_edd_edddownload',
            $_POST['_edd_edddownload']
        );
    }
	}

  public function get_meta_count($download_id){
    $desired_meta_field = "_edd_edddownload";
    $count = get_post_meta( $download_id, $desired_meta_field, true );
    if (empty($count)){
      $count = 0;
    }
    return $count;
  }

}

}

$DownloadsCount = DownloadCount::get_instance();
