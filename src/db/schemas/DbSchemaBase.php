<?php

namespace DbTools\db\schemas;

use DbToolsExport\dbvalues\DbValues;
use DbTools\db\values\DbCheckValues;
use DbTools\DbToolsModule;
use Yii;
use yii\db\Exception;
use yii\helpers\FileHelper;

class DbSchemaBase
{
    const REMOVED_FILE_CONTENT = '/* -- REMOVED -- */';
    const FLAGS_WARNING = 'warning';
    const FLAGS_DEPRECATED = 'deprecated';
    const FLAGS_LEGACY = 'legacy';
    const FLAGS_DEVEL = 'devel';
    const FLAGS_TODO = 'todo';
    const FLAGS_EXPORT = 'export';
    const FLAGS_SELECT = 'select';
    const FLAGS_USEDBY = 'usedBy';
    const FLAGS_CONSTANT = 'constant';
    const FLAGS_ALL = [
        self::FLAGS_WARNING,
        self::FLAGS_DEPRECATED,
        self::FLAGS_LEGACY,
        self::FLAGS_DEVEL,
        self::FLAGS_TODO,
        self::FLAGS_EXPORT,
        self::FLAGS_SELECT,
        self::FLAGS_USEDBY,
        self::FLAGS_CONSTANT,
    ];
    /** @var string */
    public $dbName;
    /** @var \yii\db\Connection */
    public $db;
    /** @var string */
    public $dir;
    /** @var bool */
    public $doCreate = true;
    /** @var bool */
    public $doFormat = true;
    /** @var bool */
    public $doOnlySvn = false;
    /** @var array */
    private static $databases = [];
    #-------------------------------------------------------------------------------------------------------------------
    # Public
    #-------------------------------------------------------------------------------------------------------------------
    public function __construct(string $dbName, \yii\db\Connection $db, string $subdir)
    {
        $this->dbName = $dbName;
        $this->db = $db;
        $this->dir = DbToolsModule::getInstance()->exportPath . '/export/' . $dbName . '/' . $subdir;
    }

    public function taskSql2File(string $name): array
    {
        $data = $this->getCreate($name);

        if (empty($data)) {
            throw new \Exception('getCreate failed', 500);
        }

        $this->setFileContent($name, $data);

        return [
            'createdb'   => $data,
            'createfile' => $data,
        ];
    }

    public function taskFile2Sql(string $name): array
    {
        $data = $this->getFileContent($name);

        if (empty($data)) {
            throw new \Exception('file was empty');
        }

        $datadb = trim(str_replace(self::REMOVED_FILE_CONTENT,'',$data));

        $this->executeSql($datadb);

        return [
            'createdb'   => $datadb,
            'createfile' => $data,
        ];
    }

    public function taskMarkAsRemoved(string $name): array
    {
        $data = $this->getFileContent($name);

        if (empty($data)) {
            throw new \Exception('file was empty');
        }

        //prev marked as removed --- grep old file content
        if (self::isRemoved($data)) {
            throw new Exception('file already marked as removed');
        }

        $content = self::REMOVED_FILE_CONTENT . "\n\n" . $data;

        $this->setFileContent($name, $content);

        try {
            $createDb = $this->getCreate($name);
        } catch (\Throwable $e) {
            $createDb = '';
        }

        return [
            'createdb'   => $createDb,
            'createfile' => $content,
        ];
    }

    public function taskMarkAsNotRemoved(string $name): array
    {
        $data = $this->getFileContent($name);

        if (empty($data)) {
            throw new \Exception('file was empty');
        }

        //prev marked as removed --- grep old file content
        if (!self::isRemoved($data)) {
            throw new Exception('file is not marked as removed');
        }

        $content = trim(substr($data,strlen(self::REMOVED_FILE_CONTENT)));

        $this->setFileContent($name, $content);

        try {
            $createDb = $this->getCreate($name);
        } catch (\Throwable $e) {
            $createDb = '';
        }

        return [
            'createdb'   => $createDb,
            'createfile' => $content,
        ];
    }

    public function taskDropAndMarkAsRemoved(string $name): array
    {
        $data = $this->getCreate($name);

        $content = self::REMOVED_FILE_CONTENT . "\n\n" . $data;

        $this->setFileContent($name, $content);

        $this->doDrop($name);

        return [
            'createdb'   => '',
            'createfile' => $content,
        ];
    }

    public function taskDrop(string $name): array
    {
        $this->doDrop($name);

        return [
            'createdb'   => '',
            'createfile' => $this->getFileContent($name),
        ];
    }

    public function info(): array
    {
        $ret = [];
        $files = [];

        if (!file_exists($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }

        $fu = FileHelper::findFiles($this->dir, ['only' => ['*.sql']]);
        foreach ($fu as $value) {
            $fileName = substr(basename($value), 0, -4);
            $files[$fileName]['createfile'] = trim(str_replace("\r", "", file_get_contents($value)));
            $files[$fileName]['filepath'] = $value;
        }

        $list = $this->getList();

        if ($this->doOnlySvn) {
            foreach ($files as $name => $value) {
                if (!isset($list[$name])) {
                    continue;
                }
                $ret[$name] = yii\helpers\ArrayHelper::merge($value, $list[$name]);
            }
            foreach ($list as $name => $value) {
                if (in_array($name, $files)) {
                    continue;
                }
                unset($list[$name]);
            }
        }
        else {
            $ret = yii\helpers\ArrayHelper::merge($files, $list);
        }

        if (!empty($list) && $this->doCreate) {
            foreach ($list as $name => $value) {
                $ret[$name]['createdb'] = trim(str_replace("\r", "", $this->getCreate($name)));
            }
        }
        $ret = $this->buildInfo($ret);

        return $ret;
    }

    public function finalize(array $data): array
    {
        $result = [];
        foreach ($data as $name => $value) {
            $info = isset($value['info']) ? $value['info'] : [];
            if (empty($info)) {
                $info = [];
            }
            if (isset($value['parse']) && !empty($value['parse']) && !empty($value['parse']['text'])) {
                $info[] = $value['parse']['text'];
            }

            $errors = isset($value['merged']['errors']) ? $value['merged']['errors'] : [];
            $uses = isset($value['merged']['uses']) ? $value['merged']['uses'] : [];
            $usedBy = isset($value['usedBy']) ? $value['usedBy'] : [];
            $selects = isset($value['merged']['select']) ? $value['merged']['select'] : [];
            $flags = isset($value['parse']) && isset($value['parse']['flags']) ? $value['parse']['flags'] : [];

            if (!empty($selects)) {
                if (count($selects) != count($value['parse']['select'])) {
                    $value['warnings'][] = 'Select count missmatch! should be ' . count($selects) . ' but is ' . count($value['parse']['select']);
                }
            }
            if (!empty($uses)) {
                $header = '';
                $body = '';
                foreach ($uses as $ttype => $tval) {
                    $header .= '<th>' . $ttype . '</th>';
                    $body .= '<td><ul>';
                    sort($tval);
                    foreach ($tval as $tname) {
                        $body .= '<li>' . self::getLink($this->dbName, $ttype, $tname) . '</li>';
                    }
                    $body .= '</ul></td>';
                }
                $ret = '<h4>Uses</h4><table class="table table-sm">
<thead class="thead-default"><tr>' . $header . '</tr></thead>
<tbody class="tbody"><tr>' . $body . '
 </tr></tbody>
</table>';
                $info[] = $ret;
            }
            if (!empty($usedBy)) {
                $header = '';
                $body = '';
                $flags[self::FLAGS_USEDBY] = 1;
                foreach ($usedBy as $ttype => $tval) {
                    $header .= '<th>' . $ttype . '</th>';
                    $body .= '<td><ul>';
                    sort($tval);
                    foreach ($tval as $tname) {
                        $body .= '<li>' . self::getLink($this->dbName, $ttype, $tname) . '</li>';
                    }
                    $body .= '</ul></td>';
                }
                $ret = '<h4>Used By</h4><table class="table table-sm">
 <thead class="thead-default"><tr>' . $header . '</tr></thead>
 <tbody class="tbody"><tr>' . $body . '
     </tr></tbody>
   </table>';
                $info[] = $ret;
            }
            if (!empty($errors)) {
                $body = '';
                foreach ($errors as $error) {
                    $body .= '<tr' . (!empty($error['warnings']) ? ' class="alert alert-danger"' : '') . '><td>' . $error['name'] . '</td><td>' . $error['value'] . '</td><td>' . $error['message'] . '</td><td>';
                    $list = '';
                    foreach ($error['uses'] as $ttype => $tval) {
                        $slist = '';
                        foreach ($tval as $tname) {
                            if ($tname == $name) {
                                continue;
                            }
                            $slist .= '<li>' . self::getLink($this->dbName, $ttype, $tname) . '</li>';
                        }
                        $list .= empty($slist) ? '' : '<li>' . $ttype . '</li><ul>' . $slist . '</ul>';
                    }
                    $body .= (empty($list)) ? '&nbsp;' : '<ul>' . $list . '</ul>';
                    $body .= '</td><td>' . implode('<br>', $error['warnings']) . '</td></tr>';
                }
                $ret = '<h4>Errors</h4>
<table class="table table-sm">
<thead class="thead-default">
<tr><th>Name</th><th>Value</th><th>Description</th><th>In</th><th>Warning</th></tr>
</thead>
<tbody class="tbody">' . $body . '
 </tbody>
</table>';
                $info[] = $ret;
            }

            $result[$name]['createdb'] = (isset($value['createdb']) ? $value['createdb'] : '');
            $result[$name]['createfile'] = (isset($value['createfile']) ? $value['createfile'] : '');
            $result[$name]['info'] = trim(implode('', $info));
            $result[$name]['filepath'] = (isset($value['filepath']) ? $value['filepath'] : '');
            $result[$name]['flags'] = $flags;
            if (isset($value['warnings']) && !empty($value['warnings'])) {
                $result[$name]['warnings'] = '<ul><li>' . implode('</li><li>', $value['warnings']) . '</li></ul>';
                $result[$name]['flags'][self::FLAGS_WARNING] = 1;
            }
            else {
                $result[$name]['warnings'] = '';
            }
        }

        return $result;
    }

    public function findUses(array $data, array $search4uses): array
    {
        $result = $data;
        foreach ($data as $name => $value) {
            if (!isset($value['body'])) {
                continue;
            }
            $result[$name]['uses'] = self::findUsesFor($value['body'], $name, $search4uses);
        }

        return $result;
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Public STATIC
    #-------------------------------------------------------------------------------------------------------------------
    public static function isRemoved(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        return (substr($content, 0, strlen(self::REMOVED_FILE_CONTENT)) == self::REMOVED_FILE_CONTENT);
    }

    public static function mergeData(array $data): array
    {
        foreach ($data as $type => $list) {
            foreach ($list as $name => $value) {
                self::mergeUses($type, $name, $data);
            }
        }

        return $data;
    }

    public static function setUsedBy(array $data): array
    {
        foreach ($data as $type => $list) {
            foreach ($list as $name => $value) {
                if (empty($value['uses'])) {
                    continue;
                }
                foreach ($value['uses'] as $ctype => $cval) {
                    foreach ($cval as $cname) {
                        $data[$ctype][$cname]['usedBy'][$type][] = $name;
                    }
                }
            }
        }

        return $data;
    }

    public static function getStatus(string $createFile, string $createDb): string
    {
        if (empty($createFile) && empty($createDb)) {
            return '';
        }

        if (self::isRemoved($createFile)) {
            return 'removed';
        }
        if ($createDb == $createFile) {
            return 'ok';
        }

        if (empty($createDb)) {
            return 'missing';
        }

        if (empty($createFile)) {
            return 'new';
        }

        return 'different';
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Protected
    #-------------------------------------------------------------------------------------------------------------------

    protected function getDbName(): string
    {
        $key = $this->db->dsn;
        if (isset(self::$databases[$key])) {
            return self::$databases[$key];
        }
        self::$databases[$key] = $this->db->createCommand('SELECT DATABASE()')->queryScalar();

        return self::$databases[$key];
    }

    protected function getBriefContent(array $data): string
    {
        return isset($data['body']) ? $data['body'] : '';
    }

    protected function getDeclaresContent(array $data): string
    {
        return $this->getBriefContent($data);
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Protected IF
    #-------------------------------------------------------------------------------------------------------------------

    protected function getList(): array
    {
        throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
    }

    protected function getCreate(string $name): string
    {
        throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
    }

    protected function doAdditionalInfo(array $data, array &$brief, array &$ret): void
    {
        throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
    }

    protected function sqlDrop(string $name): string
    {
        throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Protected STATIC
    #-------------------------------------------------------------------------------------------------------------------

    protected static function getLink(string $db, string $group, string $name): string
    {
        return '<button type="button" class="btn btn-link" onclick="selectItem(\'' . $db . '|' . $group . '|' . $name . '\');">' . $name . '</button>';
    }

    protected static function inBody(string $body, string $find, int $startPos = 0): bool
    {
        if (empty($body) || empty($find)) {
            return false;
        }
        $arrPrevchar = [
            ' ',
            '(',
            ')',
            '`',
            '\'',
            '"',
            '.',
            ',',
            ';',
            '=',
            "\n",
            "\r",
            "\t",
        ];
        $arrNextchar = [
            ' ',
            '(',
            ')',
            '`',
            '\'',
            '"',
            '.',
            ',',
            ';',
            '=',
            "\n",
            "\r",
            "\t",
        ];
        $pos = $startPos;

        while ($pos < strlen($body) && $pos = strpos($body, $find, $pos)) {
            $prevchar = substr($body, $pos - 1, 1);
            $pos += strlen($find);
            $nextchar = substr($body, $pos, 1);
            $pos += 1;
            if (in_array($prevchar, $arrPrevchar) && in_array($nextchar, $arrNextchar)) {
                return true;
            }
        }

        return false;
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Private
    #-------------------------------------------------------------------------------------------------------------------

    private function buildInfo(array $data): array
    {
        $ret = $data;
        foreach ($data as $name => $value) {
            try {
                $ret[$name]['parse'] = $this->doInfo($value);
                if (isset($ret[$name]['parse']['warnings'])) {
                    $ret[$name]['warnings'] = $ret[$name]['parse']['warnings'];
                    unset($ret[$name]['parse']['warnings']);
                }
                if (isset($ret[$name]['parse']['body'])) {
                    $ret[$name]['body'] = $ret[$name]['parse']['body'];
                    unset($ret[$name]['parse']['body']);
                }
            }
            catch (\Throwable $e) {
                $msg = 'class(' . get_called_class() . ') name(' . $name . ') msg(' . $e->getMessage() . ')';
                throw new \Exception($msg, DbValues::eError_General_Error, $e);
            }
        }

        return $ret;
    }

    private function doInfo(array $data): array
    {
        $ret = [
            'text'     => [],
            'declares' => [],
            'select'   => [],
            'warnings' => [],
            'export'   => [],
            'flags'    => [],
        ];

        $brief = self::parseBrief($this->getBriefContent($data));
        $declares = self::parseDeclares($this->getDeclaresContent($data));

        $ret['select'] = $brief['select'];
        unset($brief['select']);

        $warnings = yii\helpers\ArrayHelper::merge($brief['warnings'], $declares['warnings']);
        unset($brief['warnings']);
        unset($declares['warnings']);
        $ret['warnings'] = $warnings;
        $ret['declares'] = $declares;

        $ret['export'] = $brief['export'];
        unset($brief['export']);

        $ret['flags'] = $brief['flags'];
        unset($brief['flags']);

        $this->doAdditionalInfo($data, $brief, $ret);

        if (isset($brief['param'])) {
            unset($brief['param']);
        }
        $additionalInfo = [];
        if (isset($brief['additionalInfo'])) {
            $additionalInfo = $brief['additionalInfo'];
            unset($brief['additionalInfo']);
        }

        $info = $brief['info'];
        unset($brief['info']);

        if (!empty($brief)) {
            throw new \Exception('forgot to process: brief:' . print_r($brief, true) . ' data:' . print_r($data, true));
        }

        #do html formating
        if ($this->doFormat) {

            $text = [];

            if (!empty($info[$key = 'deprecated'])) {
                $text[] = '
<h4>Deprecated</h4>
<div class="alert alert-warning">
    <p>' . implode('<br>', $info[$key]) . '</p>
</div>';
            }

            if (!empty($info[$key = 'legacy'])) {
                $text[] = '
<h4>Legacy</h4>
<div class="alert alert-warning">
    <p>' . implode('<br>', $info[$key]) . '</p>
</div>';
            }

            if (!empty($info[$key = 'devel'])) {
                $text[] = '
<h4>In Development</h4>
<div class="alert alert-warning">
    <p>' . implode('<br>', $info[$key]) . '</p>
</div>';
            }

            if (!empty($info[$key = 'todo'])) {
                $text[] = '
<h4>Todo</h4>
<div class="alert alert-success">
    <ul><li>' . implode('</li><li>', $info[$key]) . '</li></ul>
</div>';
            }

            if (!empty($info[$key = 'brief'])) {
                $text[] = '
<h4>Brief</h4>
<div class="alert alert-warning">
    <p>' . implode('<br>', $info[$key]) . '</p>
</div>';
            }

            if (!empty($info[$key = 'note'])) {
                $text[] = '
<h4>Note</h4>
<div class="alert alert-info">
    <ul><li>' . implode('</li><li>', $info[$key]) . '</li></ul>
</div>';
            }

            if (!empty($info[$key = 'export'])) {
                $text[] = '
<h4>Export</h4>
<div class="alert alert-info">
    <p>' . implode('<br>', $info[$key]) . '</p>
</div>';
            }

            if (!empty($info[$key = 'select'])) {
                $text[] = '
<h4>Select</h4>
<div class="alert alert-info">
    <ul><li>' . implode('</li><li>', $info[$key]) . '</li></ul>
</div>';
            }

            foreach ($additionalInfo as $a) {
                $text[] = $a;
            }

            $ret['text'] = implode('<br>', $text);
        }

        return $ret;
    }

    private function doDrop(string $name): void
    {
        $sql = $this->sqlDrop($name);
        $this->executeSql($sql);
    }

    private function findUsesFor(string $body, string $name, array $search4uses): array
    {
        $ret = [];
        $body = self::removeComments($body);
        $body = trim(str_replace("\t", " ", $body));
        if (empty($body)) {
            return $ret;
        }
        foreach ($search4uses as $find => $type) {
            if ($find == $name) {
                continue;
            }
            if (self::inBody($body, $find)) {
                $ret[$type][] = $find;
            }
        }

        return $ret;
    }

    private function executeSql(string $data): void
    {
        if (empty($data)) {
            return;
        }
        $data = str_replace("\r", '', $data);
        $sqls = array_filter(explode(DbToolsModule::getInstance()->exportDelimiter, $data), 'strlen');

        $transaction = $this->db->beginTransaction();
        try {
            foreach ($sqls as $sql) {
                $sql = trim($sql);
                if (strtoupper(substr($sql, 0, 9)) == 'DELIMITER' || strtoupper(substr($sql, 0, 4)) == 'USE ') {
                    continue;
                }
                $this->db->createCommand($sql)->execute();
            }
            $transaction->commit();
        }
        catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function filepath(string $name): string
    {
        return $this->dir . '/' . $name . '.sql';
    }

    private function getFileContent(string $name): string
    {
        $filepath = $this->filepath($name);

        if (!file_exists($filepath)) {
            return '';
        }

        return file_get_contents($filepath);
    }

    private function setFileContent(string $name, string $content): void
    {
        $filepath = $this->filepath($name);

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        if (file_put_contents($filepath, $content) === false) {
            throw new \Exception('failed to write file: ' . $name, 500);
        }
    }

    #-------------------------------------------------------------------------------------------------------------------
    # Private STATIC
    #-------------------------------------------------------------------------------------------------------------------

    private static function brief2array(string $info): array
    {
        $ret = [];
        $info = str_replace("\t", " ", $info);
        $infos = explode("\n", $info);
        $currentcat = 'brief';
        $knowncat = [
            '@brief'      => 'brief',
            '/brief'      => 'brief',
            '@param'      => 'param',
            '/param'      => 'param',
            '@returns'    => 'return',
            '/returns'    => 'return',
            '@return'     => 'return',
            '/return'     => 'return',
            '@warnings'   => 'warnings',
            '/warnings'   => 'warnings',
            '@warning'    => 'warnings',
            '/warning'    => 'warnings',
            '@notes'      => 'note',
            '/notes'      => 'note',
            '@note'       => 'note',
            '/note'       => 'note',
            '@select'     => 'select',
            '/select'     => 'select',
            '@export'     => 'export',
            '/export'     => 'export',
            '@todo'       => 'todo',
            '/todo'       => 'todo',
            '@deprecated' => 'deprecated',
            '/deprecated' => 'deprecated',
            '@legacy'     => 'legacy',
            '/legacy'     => 'legacy',
            '@devel'      => 'devel',
            '/devel'      => 'devel',
            '@constant'   => 'constant',
            '/constant'   => 'constant',
        ];
        $current = [];
        foreach ($infos as $row) {
            $row = trim($row);
            if (empty($row)) {
                continue;
            }
            if (substr($row, 0, 1) != '@' && substr($row, 0, 1) != '/') {
                $current[] = $row;
                continue;
            }
            $found = false;
            foreach ($knowncat as $k => $d) {
                if (substr($row, 0, strlen($k)) == $k) {
                    if (!empty($current)) {
                        $ret[$currentcat][] = $current;
                    }
                    $currentcat = $d;
                    $current = [];
                    $current[] = trim(substr($row, strlen($k)));
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }
            $ret[$currentcat][] = $current;
            $currentcat = 'unknown';
            $current[] = $row;
            $msg = 'Unknown Brief Type! row:' . $row . ' info:' . $info;
            throw new \Exception($msg);
        }
        if (!empty($current)) {
            $ret[$currentcat][] = $current;
        }

        return $ret;
    }

    private static function brief2text(string $key, array &$in, array &$out): bool
    {
        if (!isset($in[$key]) || empty($in[$key])) {
            return false;
        }

        $pret = '';
        foreach ($in[$key] as $bdata) {
            if (empty($bdata)) {
                continue;
            }
            $pret .= implode("\n", $bdata);
        }
        unset($in[$key]);

        $out[$key] = $pret;

        return true;
    }

    private static function brief2list(string $key, array &$in, array &$out): bool
    {
        if (!isset($in[$key]) || empty($in[$key])) {
            return false;
        }

        $pret = [];
        foreach ($in[$key] as $bdata) {
            if (empty($bdata)) {
                continue;
            }
            foreach ($bdata as $li) {
                if (empty($bdata)) {
                    continue;
                }
                $pret[] = $li;
            }
        }
        unset($in[$key]);

        $out[$key] = $pret;

        return true;
    }

    private static function parseBrief(string $content): array
    {
        $ret = [
            'info'     => [],
            'param'    => [],
            'select'   => [],
            'export'   => [],
            'warnings' => [],
            'flags'    => [],
        ];

        $data = self::getBetween($content, '/**', '*/');
        if (empty($data)) {
            return $ret;
        }

        $in = self::brief2array(array_shift($data));

        $out = [];

        //----------------------------------------------------------------------------------------------
        // To Text
        //----------------------------------------------------------------------------------------------
        if (self::brief2text($key = 'deprecated', $in, $out)) {
            $ret['warnings'][] = 'deprecated';
            $ret['flags'][self::FLAGS_DEPRECATED] = 1;
            if (empty($out[$key])) {
                $ret['info'][$key][] = 'deprecated';
            }
            else {
                $ret['info'][$key][] = $out[$key];
            }
        }

        if (self::brief2text($key = 'legacy', $in, $out)) {
            $ret['flags'][self::FLAGS_LEGACY] = 1;
            if (empty($out[$key])) {
                $ret['info'][$key][] = 'legacy';
            }
            else {
                $ret['info'][$key][] = $out[$key];
            }
        }

        if (self::brief2text($key = 'devel', $in, $out)) {
            $ret['warnings'][] = 'devel';
            $ret['flags'][self::FLAGS_DEVEL] = 1;
            if (empty($out[$key])) {
                $ret['info'][$key][] = 'devel';
            }
            else {
                $ret['info'][$key][] = $out[$key];
            }
        }

        if (self::brief2list($key = 'todo', $in, $out)) {
            $ret['flags'][self::FLAGS_TODO] = 1;
            $ret['info'][$key] = $out[$key];
        }

        if (self::brief2text($key = 'brief', $in, $out)) {
            $ret['info'][$key][] = $out[$key];
        }

        if (self::brief2list($key = 'note', $in, $out)) {
            $ret['info'][$key] = $out[$key];
        }

        //cache select and set auto export!
        if (isset($in['export'])) {
            $ret['flags'][self::FLAGS_EXPORT] = 1;
            $ret['export'][] = 'export'; #todo maybe use export types like PHP, CPP
        }

        if (isset($in['select'])) {
            $ret['flags'][self::FLAGS_SELECT] = 1;
            if (!isset($in['export'])) {
                $in['export'][] = '';
            }
            foreach ($in['select'] as $bdata) {
                $line = $bdata[0];
                $name = trim(substr($line, 0, strpos($line, ' ')));
                if (empty($name)) {
                    $name = trim($line);
                }
                $ret['select'][] = $name;
            }
        }

        if (self::brief2text($key = 'export', $in, $out)) {
            if (empty($out[$key])) {
                $out[$key] = 'export';
            }

            $ret['info'][$key][] = $out[$key];
        }

        if (self::brief2list($key = 'select', $in, $out)) {
            $ret['info'][$key] = $out[$key];
        }

        if (self::brief2text($key = 'return', $in, $out)) {
            $ret['info'][$key][] = $out[$key];
        }

        if (isset($in['param'])) {
            $ret['param'] = $in['param'];
            unset($in['param']);
        }

        if (isset($in['warnings']) && !empty($in['warnings'])) {
            foreach ($in['warnings'] as $bdata) {
                if (empty($bdata)) {
                    continue;
                }
                $ret['warnings'][] = implode(' ', $bdata);
            }
            unset($in['warnings']);
        }

        //cache select and set auto export!
        if (isset($in['constant'])) {
            $ret['flags'][self::FLAGS_CONSTANT] = 1;
            unset($in['constant']);
        }

        if (!empty($in)) {
            throw new \Exception("forgot to process:\nin:" . print_r($in, true) . "\nret:" . print_r($ret, true));
        }

        return $ret;
    }

    private static function checkHandler($dec): array
    {
        $s = array_values(array_filter(explode(' ', $dec), 'strlen'));
        if (!isset($s[3]) || strtoupper($s[1]) != "HANDLER") {
            return [];
        }

        $ret['type'] = strtoupper($s[0]);
        $ret['condition'] = [];

        unset($s[0]); // Type
        unset($s[1]); // HANDLER
        unset($s[2]); // FOR

        foreach ($s as $p) {
            $p = trim($p);
            if (strtoupper($p) == 'BEGIN') {
                break;
            }

            $ret['condition'][] = $p;
        }
        if (!empty($ret['condition'])) {
            $ret['condition'] = implode(' ', $ret['condition']);
        }

        return $ret;
    }

    private static function splitDeclare(string $dec): array
    {
        $s = array_filter(explode(' ', $dec), 'strlen');
        //var_dump($s);

        $ret = [
            'name'  => '',
            'type'  => [],
            'value' => [],
        ];

        $next = 'name';
        foreach ($s as $p) {
            switch ($next) {
                case 'name':
                    $ret['name'] = trim($p);
                    $next = 'type';
                    break;
                case 'type':
                    if (strtoupper($p) == 'DEFAULT') {
                        $next = 'value';
                    }
                    else {
                        $ret['type'][] = trim($p);
                    }
                    break;
                case 'value':
                    $ret['value'][] = trim($p);
                    break;
                default:
                    throw new \Exception('unknown declare-next:' . print_r($dec, true));
            }
        }
        $ret['type'] = trim(strtoupper(implode(' ', $ret['type'])));
        if (empty($ret['value'])) {
            $ret['value'] = 'NULL';
        }
        else {
            $ret['value'] = trim(implode(' ', $ret['value']));
        }

        return $ret;
    }

    private static function parseDeclares(string $body): array
    {
        $ret = [
            'member'   => [],
            'error'    => [],
            'const'    => [],
            'unknown'  => [],
            'handler'  => [],
            'warnings' => [],
        ];
        if (empty($body)) {
            return $ret;
        }

        $body = self::removeComments($body);
        $body = trim(str_replace("\t", " ", $body));

        if (empty($body)) {
            return $ret;
        }

        $decl['error'] = [];
        $decl['const'] = [];

        $tdec = self::getBetween($body, 'DECLARE', ';');

        foreach ($tdec as $pos => $dec) {
            $p = [];
            $dec = str_replace('`', '', $dec);
            $dec = trim($dec);
            if (strtoupper(substr($dec, 0, 8) == 'CONTINUE')) {
                //ignore
            }
            else if (substr($dec, 0, 2) == 'm_') {
                $p = self::splitDeclare($dec);
                $ret['member'][] = $p;
                if (!self::inBody($body, $p['name'], $pos)) {
                    $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: unused';
                }
            }
            else if (substr($dec, 0, 7) == 'eError_') {
                $p = DbCheckValues::checkError(self::splitDeclare($dec));
                $ret['error'][] = $p;
                $decl['error'][] = $p['name'];
                if (isset($p['warnings']) && !empty($p['warnings'])) {
                    foreach ($p['warnings'] as $w) {
                        $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: ' . $w;
                    }
                }
                if (!self::inBody($body, $p['name'], $pos)) {
                    $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: unused';
                }
            }
            else if (substr($dec, 0, 7) == 'cConst_') {
                $p = DbCheckValues::checkConst(self::splitDeclare($dec));
                $ret['const'][] = $p;
                $decl['const'][] = $p['name'];
                if (isset($p['warnings']) && !empty($p['warnings'])) {
                    foreach ($p['warnings'] as $w) {
                        $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: ' . $w;
                    }
                }
                if (!self::inBody($body, $p['name'], $pos)) {
                    $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: unused';
                }
            }
            else {
                $h = self::checkHandler($dec);
                if (!empty($h)) {
                    $ret['handler'][] = $h;
                }
                else {
                    $p = self::splitDeclare($dec);
                    $ret['unknown'][] = $p;
                    $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: invalid declare formating ["DECLARE ' . $dec . '"]';
                    if (!self::inBody($body, $p['name'], $pos)) {
                        $ret['warnings'][] = 'DECLARE [ ' . $p['name'] . ' ]: unused';
                    }
                }
            }
        }

        //find undeclared values
        {
            foreach (DbValues::Keys['error'] as $find) {
                if (in_array($find, $decl['error'])) {
                    continue;
                }
                if (self::inBody($body, $find)) {
                    $ret['warnings'][] = 'DECLARE [ ' . $find . ' ]: not declared';
                }
            }

            foreach (DbValues::Keys['const'] as $find) {
                if (in_array($find, $decl['const'])) {
                    continue;
                }
                if (self::inBody($body, $find)) {
                    $ret['warnings'][] = 'DECLARE [ ' . $find . ' ]: not declared';
                }
            }
        }

        return $ret;
    }

    private static function getBetween(string $str, string $startDelimiter, string $endDelimiter): array
    {
        if (empty($str) || empty($startDelimiter) || empty($endDelimiter)) {
            return [];
        }
        $contents = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[$contentEnd + $endDelimiterLength] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $contents;
    }

    private static function mergeUses(string $type, string $name, array &$infos): bool
    {
        if (empty($name)) {
            return false;
        }
        if (isset($infos[$type]) && isset($infos[$type][$name]) && isset($infos[$type][$name]['merged'])) {
            return true;
        }
        if (!isset($infos[$type][$name])) {
            return false;
        }

        $infos[$type][$name]['merged']['uses'] = [];

        $infos[$type][$name]['merged']['select'] = $infos[$type][$name]['parse']['select'];

        $infos[$type][$name]['merged']['errors'] = $infos[$type][$name]['parse']['declares']['error'];

        #set uses this item
        foreach ($infos[$type][$name]['merged']['errors'] as $id => $cval) {
            $infos[$type][$name]['merged']['errors'][$id]['uses'][$type][] = $name;
        }

        if (isset($infos[$type][$name]['uses']) && !empty($infos[$type][$name]['uses'])) {
            $infos[$type][$name]['merged']['uses'] = $infos[$type][$name]['uses'];

            foreach ($infos[$type][$name]['uses'] as $ctype => $cval) {
                foreach ($cval as $cname) {
                    $tret = self::mergeUses($ctype, $cname, $infos);
                    if (empty($tret)) {
                        continue;
                    }

                    if (!empty($infos[$ctype][$cname]['merged']['select'])) {
                        if (empty($infos[$type][$name]['merged']['select'])) {
                            $infos[$type][$name]['merged']['select'] = $infos[$ctype][$cname]['merged']['select'];
                        }
                        else {
                            foreach ($infos[$ctype][$cname]['merged']['select'] as $mname) {
                                if (in_array($mname, $infos[$type][$name]['merged']['select'])) {
                                    continue;
                                }
                                $infos[$type][$name]['merged']['select'][] = $mname;
                            }
                        }
                    }
                    if (!empty($infos[$ctype][$cname]['merged']['errors'])) {
                        foreach ($infos[$ctype][$cname]['merged']['errors'] as $mval) {
                            $found = false;
                            foreach ($infos[$type][$name]['merged']['errors'] as $oid => $oval) {
                                if ($mval['name'] == $oval['name'] && $mval['value'] == $oval['value']) {
                                    foreach ($mval['uses'] as $mtype => $mcval) {
                                        if (!isset($infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype])) {
                                            $infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype] = $mcval;
                                        }
                                        else {
                                            foreach ($mcval as $mname) {
                                                if (in_array($mname, $infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype])) {
                                                    continue;
                                                }
                                                $infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype][] = $mname;
                                            }
                                        }
                                    }
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $infos[$type][$name]['merged']['errors'][] = $mval;
                            }
                        }
                    }
                    if (!empty($infos[$ctype][$cname]['merged']['uses'])) {
                        foreach ($infos[$ctype][$cname]['merged']['uses'] as $mtype => $mval) {
                            if (!isset($infos[$type][$name]['merged']['uses'][$mtype])) {
                                $infos[$type][$name]['merged']['uses'][$mtype] = $mval;
                            }
                            else {
                                foreach ($mval as $mname) {
                                    if (in_array($mname, $infos[$type][$name]['merged']['uses'][$mtype])) {
                                        continue;
                                    }
                                    $infos[$type][$name]['merged']['uses'][$mtype][] = $mname;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private static function removeComments(string $source): string
    {
        $RXSQLComments = '@(--[^\r\n]*)|(\#[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)@ms';

        return ((empty($source)) ? '' : preg_replace($RXSQLComments, '', $source));
    }
}
