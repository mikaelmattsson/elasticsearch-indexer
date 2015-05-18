<div id="esi-profiler">
    <table class="esi-totals">
        <thead>
        <tr>
            <th>Total MySQL Queries</th>
            <th>Total MySQL Time</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?php echo $totalCount ?></td>
            <td><?php echo number_format($totalTime * 1000, 3, '.', '&nbsp;') ?>&nbsp;ms</td>
        </tr>
        </tbody>
    </table>
    <table>
        <tbody>
        <?php foreach ($queries as $query) : ?>
            <tr>
                <td class="esi-sql"><?php echo $query[0] ?></td>
                <td class="esi-time"><?php echo number_format($query[1] * 1000, 3, '.', '&nbsp;') ?>&nbsp;ms</td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>