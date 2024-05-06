<?php declare(strict_types=1);

/**
 * Provides useful stuff to fix DB issues.
 */
final class SqlFixer {

    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Fixes last_value for all sequences in DB.
     *
     * This method will update sequences by setting max id from related table
     * as a last_value.
     * Note: for now it works with PostgreSQL only.
     */
    public function fixSequences() {
        $sequences = $this->getSequences();

        foreach ($sequences as $sequence) {
            $data = $this->getTableData($sequence);
            $maxId = $this->getTableMaxId($data['table'], $data['key']);
            $lastId = $this->getSequenceLastId($sequence);

            // Update last_value if it's lower than actual id in related table.
            if ($maxId > $lastId) {
                $this->updateSequenceLastId($sequence, $maxId + 1);
                $this->log("Updated $sequence ($lastId => $maxId).");
            }
            else {
                $this->log("Skip $sequence.");
            }
        }

        $this->log('Sequence last_value fix complete.');
    }

    /**
     * Returns sequences from DB.
     *
     * @return string[]
     *   The sequence names.
     */
    private function getSequences(): array {
        $query = $this->connection->query("SELECT relname FROM pg_class WHERE relkind = 'S' order BY relname");
        $query->execute();
        return $query->fetchCol();
    }

    /**
     * Returns last id from sequence.
     *
     * @param string $sequence
     *   The sequence name.
     *
     * @return int
     *   The sequence last_value.
     */
    private function getSequenceLastId(string $sequence): int {
        $query = $this->connection->query("SELECT last_value FROM \"$sequence\"");
        $query->execute();
        return (int) $query->fetchField();
    }

    /**
     * Updates last id in sequence.
     *
     * @param string $sequence
     *   The sequence name.
     * @param int $newId
     *   New id.
     */
    private function updateSequenceLastId(string $sequence, int $newId): void {
        $this->connection
            ->query("ALTER SEQUENCE \"$sequence\" RESTART $newId")
            ->execute();
    }

    /**
     * Returns table key and table name from sequence name.
     *
     * Here we assume that sequence name contains the table name and a key name.
     * For example table `content` has related sequence with a name that matches
     * one of the following:
     * - content_id_seq
     * - content_cid_seq
     * - content_revision_id_seq
     * - content_item_id_seq
     * - content_value_seq
     * - seq_content (in this case key is `id`).
     *
     * @param string $sequence
     *   The sequence name.
     *
     * @return string[]
     *   The table key and table name that is related to the specified sequence.
     */
    private function getTableData(string $sequence): array {
        $res = [];
        $match = [];
        preg_match_all('!(_([a-z]{0,2}id)|_revision_id|_item_id|_value)_seq$!', $sequence, $match);
        $match[1][0] = $match[1][0] ?? '';

        // Get key from sequence name.
        if ($match[1][0] === '_revision_id') {
            $res['key'] = 'revision_id';
        }
        elseif ($match[1][0] === '_item_id') {
            $res['key'] = 'item_id';
        }
        elseif ($match[1][0] === '_value') {
            $res['key'] = 'value';
        }
        else {
            $res['key'] = $match[2][0] ?? 'id';
        }

        // Get table name from sequence name.
        $res['table'] = preg_replace('!^seq_!', '', $sequence);
        $res['table'] = preg_replace('!^' . $this->connection->getPrefix() . '!', '', $res['table']);
        $res['table'] = !empty($match[0][0])
            ? preg_replace('!' . $match[0][0] . '$!', '', $res['table'])
            : $res['table'];

        if ($res['table'] === 'users' && $res['key'] === 'id') {
            $res['key'] = 'uid';
        }

        return $res;
    }

    /**
     * Returns max id from DB table.
     *
     * @param string $table
     *   The table name.
     * @param string $key
     *   The table key field name.
     *
     * @return int
     *   The max id.
     */
    private function getTableMaxId(string $table, string $key = 'id'): int {
        $query = $this->connection->select($table, 't');
        $query->addExpression("MAX($key)", 'max');
        return (int) $query->execute()->fetchField();
    }

    /**
     * Prints message on the screen.
     *
     * @param string $message
     *   The message.
     */
    private function log(string $message): void {
        echo "$message\n";
    }

}
