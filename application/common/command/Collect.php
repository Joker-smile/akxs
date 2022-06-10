<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/6/28
 * Time: 15:03
 */

namespace app\common\command;

use app\admin\model\News;
use app\admin\model\Novel;
use app\admin\model\NovelChapter;
use think\console\input\Option;
use think\Db;
use think\facade\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use org\File;
use think\facade\Env;
use think\Request;

define('APP_PATH', __DIR__ . '/../../');
include_once APP_PATH . 'admin/common.php';

class Collect extends Command
{
    protected $booksUrl = "http://www.01kanshu.com/Interface/CommonApi/books?channel=wkmitu";
    protected $bookInfoUrl = "http://www.01kanshu.com/Interface/CommonApi/Bookinfo?channel=wkmitu&bookid=%s";
    protected $bookChaptersUrl = "http://www.01kanshu.com/Interface/CommonApi/chapters?channel=wkmitu&bookid=%s";
    protected $bookChapterInfoUrl = "http://www.01kanshu.com/Interface/CommonApi/ChapterInfo?channel=wkmitu&bookid=%s&chapterid=%s";

    protected function configure()
    {
        $this->setName('collect')
            ->addOption('type', null, Option::VALUE_REQUIRED, 'type name')
            ->setDescription("采集小说");
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');

        //创建小说
        if ($type == 'create') {
            $this->create();
        }

        //更新小说章节
        if ($type == 'update') {
            $this->update();
        }

        //补漏小说遗漏章节
        if ($type == 'trap') {
            $this->trap();
        }

        if ($type == 'artisan') {

            $novels = Novel::all();
            foreach ($novels as $novel) {
                if ($novel['pic']) {
                    continue;
                }

                $this->downloadImage($novel);
            }

            var_dump('完成迁移');
        }

        if ($type == 'news') {
            News::where('id', '>', 0)->delete();
            $novels = Novel::order('update_time', 'desc')->limit(7)->all();
            foreach ($novels as $novel) {

                News::create([
                    'nid' => $novel['id'],
                    'title' => $novel['title'],
                    'category' => $novel['category'],
                    'pic' => $novel['pic'],
                    'content' => $novel['content'],
                    'up' => $novel['up'],
                    'down' => $novel['down'],
                    'hits' => $novel['hits'],
                    'update_time' => $novel['update_time'],
                    'create_time' => $novel['create_time'],
                    'status' => $novel['status'],
                    'position' => $novel['position'],
                    'reurl' => $novel['reurl'],
                    'template' => $novel['template'],
                    'hits_day' => $novel['hits_day'],
                    'hits_week' => $novel['hits_week'],
                    'hits_month' => $novel['hits_month'],
                    'hits_time' => $novel['hits_time'],
                ]);
            }

            var_dump('完成迁移');
        }

    }

    public function trap()
    {
        $novels = Novel::all();

        foreach ($novels as $novel) {
            $chatper = NovelChapter::where('novel_id', $novel['id'])->find();
            if ($chatper) {
                continue;
            }

            $bookChatpers = $this->curlGet(sprintf($this->bookChaptersUrl, $novel['source_bookid']));
            $bookChatpers = json_decode($bookChatpers, true);
            $chapterlists = [];
            foreach ($bookChatpers['result'] as $key => $chapterlist) {
                $chapterlists = array_merge($chapterlists, $chapterlist['chapterlist']);
            }

            $this->createChapter($chapterlists, $novel);
        }
    }

    protected function update()
    {
        $novels = Novel::where('serialize', 0)->all();

        foreach ($novels as $novel) {
            $chatper = NovelChapter::where('novel_id', $novel['id'])->find();
            if (!$chatper) {
                continue;
            }
            $chatpers = json_decode(@gzuncompress(base64_decode($chatper['chapter'])), true);
            $bookChatpers = $this->curlGet(sprintf($this->bookChaptersUrl, $novel['source_bookid']));
            $bookChatpers = json_decode($bookChatpers, true);
            $chapterlists = [];
            foreach ($bookChatpers['result'] as $key => $chapterlist) {
                $chapterlists = array_merge($chapterlists, $chapterlist['chapterlist']);
            }

            if (count($chatpers) < count($chapterlists)) {
                $length = count($chapterlists) - count($chatpers);
                $new_chapters = array_slice($chapterlists, -$length);
                $this->updateChapter($new_chapters, $novel, $chatpers);
            }
        }
    }

    protected function create()
    {
        $booksUrl = $this->booksUrl;
        $books = json_decode($this->curlGet($booksUrl), true);

        if (empty($books['result'])) {
            return "书籍列表为空";
        }

        foreach ($books['result'] as $book) {
            $bookInfoUrl = sprintf($this->bookInfoUrl, $book['bookid']);
            $bookInfo = json_decode($this->curlGet($bookInfoUrl), true);
            $bookInfo = $bookInfo['result'];
            $novel = $this->createNovel($bookInfo, $bookInfoUrl);
            if (!$novel) {
                continue;
            }
            $this->downloadImage($novel);
            $bookChatpersUrl = sprintf($this->bookChaptersUrl, $novel['source_bookid']);
            $bookChatpers = json_decode($this->curlGet($bookChatpersUrl), true);
            $chapterlists = [];
            foreach ($bookChatpers['result'] as $key => $chapterlist) {
                $chapterlists = array_merge($chapterlists, $chapterlist['chapterlist']);
            }

            $this->createChapter($chapterlists, $novel);
        }
    }


    protected function updateChapter($new_chapters, $novel, $old_chapters)
    {
        foreach ($new_chapters as $chapter) {
            $key = uniqidReal();
            $path = $novel['id'] . DIRECTORY_SEPARATOR . $key . '.txt';
            $bookChapterInfoUrl = sprintf($this->bookChapterInfoUrl, $novel['source_bookid'], $chapter['chapterid']);
            $chapter_content = json_decode($this->curlGet($bookChapterInfoUrl), true);
            $chapters_data['chapter'][$key] = [
                'title' => $chapter['chaptername'],
                'intro' => '',
                'update_time' => time(),
                'issued' => 1,
                'word' => $chapter['number'],
                'status' => 1,
                'auto' => 0,
                'path' => $novel['id'] . DIRECTORY_SEPARATOR . $key . '.txt',
            ];
            self::set_chapter_content($path, $chapter_content['result']['content']);
        }

        $chapters = array_merge($old_chapters, $chapters_data['chapter']);
        $data['chapter'] = base64_encode(gzcompress(json_encode($chapters), 4));
        Db::name('novel_chapter')->where('novel_id', $novel['id'])->update($data);
        Db::name('novel')->where(['id' => $novel['id']])->update(['update_time' => time()]);
        var_dump('更新小说:' . $novel['id']);

    }

    protected function createChapter($chapters, $novel)
    {
        foreach ($chapters as $chapter) {
            $key = uniqidReal();
            $path = $novel['id'] . DIRECTORY_SEPARATOR . $key . '.txt';
            $bookChapterInfoUrl = sprintf($this->bookChapterInfoUrl, $novel['source_bookid'], $chapter['chapterid']);
            $chapter_content = json_decode($this->curlGet($bookChapterInfoUrl), true);
            $chapters_data['chapter'][$key] = [
                'title' => $chapter['chaptername'],
                'intro' => '',
                'update_time' => time(),
                'issued' => 1,
                'word' => $chapter['number'],
                'status' => 1,
                'auto' => 0,
                'path' => $novel['id'] . DIRECTORY_SEPARATOR . $key . '.txt',
            ];
            self::set_chapter_content($path, $chapter_content['result']['content']);
        }
        $chapter_data['chapter'] = base64_encode(gzcompress(json_encode($chapters_data['chapter']), 4));
        $chapter_data['novel_id'] = $novel['id'];
        $chapter_data['source'] = '愚猫看书';
        Db::name('novel_chapter')->insert($chapter_data);
        Db::name('novel')->where(['id' => $novel['id']])->update(['update_time' => time()]);
        var_dump('添加小说:' . $novel['id']);
    }

    protected function createNovel($bookInfo, $bookInfoUrl)
    {
        if (Novel::where('source_bookid', $bookInfo['bookid'])->find()) {
            return '';
        }

        $data['source_bookid'] = $bookInfo['bookid'];
        $data['category'] = $this->getCategory($bookInfo['bookType']);
        $data['title'] = $bookInfo['bookname'];
        $data['content'] = $bookInfo['intro'];
        $data['author'] = $bookInfo['authorname'];
        $data['source_pic_url'] = $bookInfo['bookpic'];
        $data['tag'] = $bookInfo['keywords'];
        $data['hits'] = rand(1000, 9000);
        $data['rating'] = rand(6, 9);
        $data['serialize'] = $bookInfo['state'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['hits_month'] = rand(1000, 90000);
        $data['reurl'] = $bookInfoUrl;
        $data['status'] = 1;
        $data['hits_time'] = time();
        $data['word'] = $bookInfo['words'];

        return Novel::create($data);
    }


    protected function getCategory($category)
    {
        $categories = [
            "古代言情" => 25,
            "女生短篇" => 40,
            "现代言情" => 26,
            "男生仙侠" => 19,
            "男生其他" => 34,
            "男生历史" => 20,
            "男生悬疑" => 23,
            "男生游戏" => 34,
            "男生玄幻" => 18,
            "男生都市" => 21,
            "职场言情" => 28,
            "都市婚姻" => 27

        ];

        return $categories[$category];
    }

    protected static function set_chapter_content($path, $content)
    {
        $content = base64_encode(gzcompress($content, 4));
        $addons_name = Cache::remember('addons_storage', function () {
            $map = ['status' => 1, 'group' => 'storage'];
            return Db::name('Addons')->where($map)->value('name');
        });
        if ($addons_name) {
            $addons_class = get_addon_class($addons_name);
            if (class_exists($addons_class)) {
                $addon = new $addons_class();
                $addon->put($path, $content);
            }
        } else {
            File::put(Env::get('runtime_path') . 'txt' . DIRECTORY_SEPARATOR . $path, $content);
        }
        return $path;
    }

    protected function curlGet($url, $param = [])
    {
        $header = array(
            'Accept: application/json',
        );
        $ch = curl_init();
        //设置抓取的url
        curl_setopt($ch, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 超时设置,以秒为单位
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        // 设置cookie
        if ($cookie = $param['cookie'] ?? '') curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        // 超时设置，以毫秒为单位
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50000);

        // 设置请求头
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $output = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // 判断是否成功
        if ($error) {
            return false;
        } else {
            return $output;
        }
    }

    protected function downloadImage($novel)
    {
        $content = file_get_contents($novel['source_pic_url']);
        $path = Env::get('runtime_path') . 'images' . DIRECTORY_SEPARATOR . $novel['id'] . '.jpg';
        File::put($path, $content);
        Novel::where('id', $novel['id'])->update(['pic' => '/runtime/images/' . $novel['id'] . '.jpg']);
        var_dump('小说id:' . $novel['id']);
    }
}
