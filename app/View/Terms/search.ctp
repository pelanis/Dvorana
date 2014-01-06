<?php
$this->Paginator->options(array(
    'update' => '#listaTermina',
    'before' => "$('#load').activity()",
    'complete' => "$('#load').activity(false)",
    'evalScripts' => true
));
?>

    <div class="terms index">
        <h2><?php echo __('Pretraga'); ?></h2>
        <?php
        if ($userData['role'] == 'Klijent') {
            ?>

            <table cellpadding="0" cellspacing="0">
                <tr>
                    <th><?php echo $this->Paginator->sort('date'); ?></th>
                    <th><?php echo $this->Paginator->sort('status'); ?></th>
                    <th><?php echo $this->Paginator->sort('term'); ?></th>
                    <th><?php echo $this->Paginator->sort('Sala'); ?></th>
                    <th><?php echo $this->Paginator->sort('comment'); ?></th>
                    <th><?php echo $this->Paginator->sort('price'); ?></th>
                </tr>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><?php echo h($term['Term']['date']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['status']); ?>&nbsp;</td>
                        <td><?php echo h($term[0]['term']); ?>&nbsp;</td>
                        <td><?php echo h($term['Hall']['name']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['comment']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['price']); ?>&nbsp;</td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php } else { ?>

            <table cellpadding="0" cellspacing="0">
                <tr>
                    <th><?php echo $this->Paginator->sort('date'); ?></th>
                    <th><?php echo $this->Paginator->sort('status'); ?></th>
                    <th><?php echo $this->Paginator->sort('term'); ?></th>
                    <th><?php echo $this->Paginator->sort('name'); ?></th>
                    <th><?php echo $this->Paginator->sort('Sala'); ?></th>
                    <th><?php echo $this->Paginator->sort('comment'); ?></th>
                    <th><?php echo $this->Paginator->sort('price'); ?></th>
                </tr>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><?php echo h($term['Term']['date']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['status']); ?>&nbsp;</td>
                        <td><?php echo h($term[0]['term']); ?>&nbsp;</td>
                        <td><?php echo h($term['User']['name']) . ' ' . h($term['User']['surname']); ?>&nbsp;</td>
                        <td><?php echo h($term['Hall']['name']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['comment']); ?>&nbsp;</td>
                        <td><?php echo h($term['Term']['price']); ?>&nbsp;</td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php } ?>


        <p>
            <?php
            echo $this->Paginator->counter(array(
                'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
            ));
            ?>    </p>

        <div class="paging">
            <?php
            echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
            echo $this->Paginator->numbers(array('separator' => ''));
            echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
            ?>
        </div>

    </div>


<?php
echo $this->Js->writeBuffer(array('cache' => TRUE));
?>