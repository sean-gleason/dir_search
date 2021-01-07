<?php

	namespace Drupal\dir_search\Plugin\Block;

	use Drupal\Core\Block\BlockBase;
	use Drupal\Core\Block\BlockPluginInterface;
	use Drupal\Core\Form\FormStateInterface;

	use Aws\Credentials\CredentialProvider;
	use Aws\Credentials\Credentials;
	use Aws\ElasticsearchService\ElasticsearchPhpHandler;

	use Aws\ElastsicsearchPhpHandler;
	use Elasticsearch\ClientBuilder;

	/**
	 * @Block(
	 *  id = "direct_search_block_display",
	 *  admin_label = @Translation("Direct Elasticsearch Block Display")
	 * )
	 * 
	 */
	class DirectBlockDisplay extends BlockBase implements BlockPluginInterface {

		public function build(){

			$queryTerm = $this->get_query_term();

			$query = $this->get_search_results($queryTerm);

			// print('<pre>');
			// print_r($query);
			// print('</pre>');

			print('<pre>');
			print_r($query);
			print('</pre>');

			$markup = '<div class="display"><ul>';

			foreach($query as $qry) {
				$markup .= '<li class="">';
				$markup .= '<h3>' . $qry['_source']['name'] . '</h3>';
				$markup .= '<p> UUID: ' . $qry['_source']['page_url'] . '</p>';
				$markup .= '</li>';
			}

			$markup .= '</ul></div>';

			$return = [
				'#markup' => $markup,
				'#cache' => [
					'max-age' => 0
				]
			];

			return $return;

		}

		public function blockForm($form, FormStateInterface $form_state) {

			$form = parent::blockForm($form, $form_state);

			$config = $this->getConfiguration();

			$form['authKey'] = [
				'#type' => 'textfield',
				'#title' => $this->t('AWS Authorization Key'),
				'#required' => true,
				'#default_value' => (isset($config['authKey'])) ? $config['authKey'] : '',
			];

			$form['authSecret'] = [
				'#type' => 'textfield',
				'#title' => $this->t('AWS Secret Key'),
				'#required' => true,
				'#default_value' => (isset($config['authSecret'])) ? $config['authSecret'] : ''
			];

			$form['path'] = [
				'#type' => 'textfield',
				'#title' => $this->t('URL Path to AWS Endpoint'),
				'#description' => $this->t('The URL to the elasticsearch endpoint on AWS. If using a secure connection via https:// please add a :443 port number to the path'),
				'#required' => true,
				'#default_value' => (isset($config['path'])) ? $config['path'] : ''
			];

			$form['index'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Index'),
				'#description' => $this->t('Please inspect the aws elasticsearch cluster for the actual name of the index'),
				'#required' => true,
				'#default_value' => (isset($config['index'])) ? $config['index'] : ''
			];


			$form['region'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Region'),
				'#description' => $this->t('The AWS Region the elasticsearch server instance is on. Should be in the form of: us-east-2'),
				'#required' => true,
				'#default_value' => (isset($config['region'])) ? $config['region'] : ''
			];

			return $form;

		}


		public function blockSubmit($form, FormStateInterface $form_state) {

			parent::blockSubmit($form, $form_state);

			$this->configuration['authKey'] = $form_state->getValue('authKey');
			$this->configuration['authSecret'] = $form_state->getValue('authSecret');
			$this->configuration['path'] = $form_state->getValue('path');
			$this->configuration['index'] = $form_state->getValue('index');
			$this->configuration['region'] = $form_state->getValue('region');

		}


		public function get_search_results($queryTerm = NULL){

			$results = 'No Results Found';

			$config = $this->getConfiguration();

			if(!$config['authKey'] || !$config['authSecret'] || !$config['path'] || !$config['index'] || !$config['region'] ) {
				return;
			} 

			// $queryTerm = 'Study';

			if(!$queryTerm) {
				$query = ['match_all' => ['boost' => 1.0]];
			} else {
				$query = ['match' => ['name' => $queryTerm]];
			}

			// $authKey = 'AKIAJKNMA747XPJM4KZA';
			// $authSecret = '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';
			// $path = 'https://search-testing-elasticsearch-dxk4eaz5yetxt7yyx3eg6xldpu.us-east-2.es.amazonaws.com:443';
			// $index = 'elasticsearch_index_pantheon_ewg_drupal_dev_elasticsearch_index';
			// $region = 'us-east-2';

			$provider = CredentialProvider::fromCredentials(
			    new Credentials($config['authKey'], $config['authSecret'])			  
			);

			$handler = new ElasticsearchPhpHandler($config['region'], $provider);

			$client = ClientBuilder::create()
				->setHandler($handler)
				->setHosts([$config['path']])
				->build();

			$queryParams = [
				'index' => $config['index'],
				'body' => [
					'query' => $query
				]
			];

			$results = $client->search($queryParams);

			return $results['hits']['hits'];

		}

		public function get_query_term(){

			return 'GLYCINATE';

		}

	}