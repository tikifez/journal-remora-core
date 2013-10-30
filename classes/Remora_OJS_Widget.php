<?php
/**
 * This class contains widgets for retrieving data from a remora-ready journal install
 */
class Remora_OJS_Widget extends WP_Widget {

	public function __construct() {
		// widget actual processes
		parent::__construct(
			'remora_ojs_widget', // Base ID
			__('Remora OJS Widget', 'remora_ojs'), // Name
			array( 'description' => __( 'Displays excerpts from journal articles', 'text_domain' ), ) // Args
			);
	}


	public function register_widget() {
		register_widget( 'Remora_OJS_Widget' );
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		$title = apply_filters( 'widget_title', $instance['title'] );
		$articles = explode(',', $instance['articles'] );
		$remoraOJS = new Remora_OJS_Core;

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

			// Show each requested article excerpt
		$ojs_articles = $remoraOJS->get_abstract_by_id($articles, array('excerpt_length'=> 10, 'db'=>cfct_get_option('cfct_ojs_db') ) );

		if(is_array($ojs_articles))
			foreach($ojs_articles as $article){
				echo '<div class="excerpt">
				<header>
				<h4 class="excerpt-title">
				<a href="'.$abstract->link.'">'.$abstract->title.'</a>
				</h4>
				<div class="excerpt-authors byline">
				'.$abstract->authors.'
				</div>
				</header>
				<div class="excerpt-text">
				'.$abstract->excerpt.'
				</div>
				<ul class="excerpt-galleys">';
				
				foreach($abstract->galleys as $galley) {
					echo "<li>$galley</li>";
				}
				echo'
				</ul>
				</div>';
			}

			echo $args['after_widget'];
		}

		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Article Excerpts', 'remora_ojs' );
			}
			if ( isset( $instance[ 'articles' ] ) ) {
				$articles = $instance[ 'articles' ];
			}
			else {
				$articles = __( '', 'remora_ojs' );
			}
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?>
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</label> 
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'articles' ); ?>"><?php _e( 'OJS Articles to Share:' ); ?>
					<input class="widefat" id="<?php echo $this->get_field_id( 'articles' ); ?>" name="<?php echo $this->get_field_name( 'articles' ); ?>" type="text" value="<?php echo esc_attr( $articles ); ?>" /><br/>
					<small>Enter OJS article IDs separated by commas.</small>
				</label> 
			</p>
			<?php 
		}

		public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
			$instance = array();
			$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
			$instance['articles'] = ( ! empty( $new_instance['articles'] ) ) ? trim( preg_replace("/[^0-9, ]/", "", $new_instance['articles']), ",") : '';

			return $instance;
		}
	}