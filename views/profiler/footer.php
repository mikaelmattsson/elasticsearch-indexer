<div id="esi-profiler">
    <table class="esi-totals">
        <thead>
        <tr>
            <th>Total MySQL Queries</th>
            <th>Total MySQL Time</th>
            <th>Total Elasticsearch Queries</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?php echo count($queries) ?>
            </td>
            <td>
                <?php echo number_format($totalTime * 1000, 3, '.', '&nbsp;') ?>&nbsp;ms
            </td>
            <td>
                <?php echo count($elasticQueries) ?>
            </td>
        </tr>
        </tbody>
    </table>
    <table>
        <tbody>
        <?php foreach ($queries as $query) : ?>
            <tr>
                <td class="esi-sql">
                    <?php echo $query[0] ?>
                </td>
                <td class="esi-time">
                    <?php echo number_format($query[1] * 1000, 3, '.', '&nbsp;') ?>&nbsp;ms
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <table>
        <tbody>
        <?php foreach ($elasticQueries as $query) : ?>
            <tr>
                <td class="esi-wpquery-args">
                    <span><?php print_r($query[1]) ?></span>
                </td>
                <td class="esi-elasticsearch-args">
                    <span><?php echo json_encode($query[0], JSON_PRETTY_PRINT) ?></span>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
