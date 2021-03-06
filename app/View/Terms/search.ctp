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
                    <th><?php echo $this->Paginator->sort('Datum'); ?></th>
                    <th><?php echo $this->Paginator->sort('Status'); ?></th>
                    <th><?php echo $this->Paginator->sort('Termin'); ?></th>
                    <th><?php echo $this->Paginator->sort('Sala'); ?></th>
                    <th><?php echo $this->Paginator->sort('Komentar'); ?></th>
                    <th><?php echo $this->Paginator->sort('Cijena'); ?></th>
                </tr>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><?php echo h($term[0]['date']); ?>&nbsp;</td>
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
                    <th><?php echo $this->Paginator->sort('Datum'); ?></th>
                    <th><?php echo $this->Paginator->sort('Status'); ?></th>
                    <th><?php echo $this->Paginator->sort('Termin'); ?></th>
                    <th><?php echo $this->Paginator->sort('Ime i prezime'); ?></th>
                    <th><?php echo $this->Paginator->sort('Sala'); ?></th>
                    <th><?php echo $this->Paginator->sort('Komentar'); ?></th>
                    <th><?php echo $this->Paginator->sort('Cijena'); ?></th>
                </tr>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><?php echo h($term[0]['date']); ?>&nbsp;</td>
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
                'format' => __('Stranica {:page} od {:pages}, prikazano {:current} termina od ukupno {:count}, počinje od {:start}, završava na {:end}')
            ));
            ?>    </p>

        <div class="paging">
            <?php
            echo $this->Paginator->prev('< ' . __('prethodni'), array(), null, array('class' => 'prev disabled'));
            echo $this->Paginator->numbers(array('separator' => ''));
            echo $this->Paginator->next(__('sljedeći') . ' >', array(), null, array('class' => 'next disabled'));
            ?>
        </div>

    </div>

<?php
echo $this->Js->writeBuffer();
?>