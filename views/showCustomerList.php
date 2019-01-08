<?php
/**
 * Вывод страницы со списком пользователей
 * Выполняется в контенсте метода CustomerList::showCustomerList
 */
?>
<div class="wrap">
	<h2><?php _e( 'Клиенты', IN_WC_CRM ); ?></h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<?php
							$this->customerTable->prepare_items();
							$this->customerTable->display(); 
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">TEST
		<?php
			$customers = $this->getCustomers();
			var_dump($customers);
		?>
	</div>
</div>