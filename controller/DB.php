<?php

class DB{
    // STEP1: 宣告類別內變數；以及建構類別內容宣告 (4-14)
    protected $table;
    protected $dsn="mysql:host=localhost; charset=utf8; dbname=db03";
    protected $pdo;     //以上用在資料庫連線
    protected $links;  //用在分頁功能

    function __construct($table)
    {
        $this->table=$table;
        $this->pdo=new PDO($this->dsn, 'root', '');  //取用protected變數設定的功能
    }


    // STEP3: 建立類別外可用的10個function: all/find/count/save/del、max/min/sum、paginate/links
    function all(...$arg){
        $sql= " SELECT * FROM $this->table ";
        $sql= $this->sql_all($sql, ...$arg);
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    function find($arg){
        $sql= " SELECT * FROM $this->table ";
        $sql= $this->sql_one($sql,$arg);
        return $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
    function count(...$arg){
        $sql= " SELECT count(*) FROM $this->table ";
        $sql= $this->sql_all($sql, ...$arg);
        return $this->pdo->query($sql)->fetchColumn();
    }
    function save($arg){
        // 以$arg中是否有id來判斷：有id為更新、無id為新增
        if(isset($arg['id'])){
            // 先取得key=value的字串
            $tmp=$this->a2s($arg);
            // 將key=value的字串引進update語法
            $sql = " UPDATE $this->table SET " . join(" , ", $tmp);
            // 再將更新的條件引入update語法
            $sql = $sql . " WHERE `id`='{$arg['id']}' ";
        }else{
            // 將陣列中的鍵s(欄位名稱)、值s(對應值)存到變數；再引入INSERT語法中
            $keys = join("`,`",array_keys($arg));
            $values = join("','",$arg);
            $sql = " INSERT INTO $this->table (`".$keys."`) VALUES ('".$values."') ";
        }
        return $this->pdo->exec($sql);  //return存入成功筆數
    }
    function del($arg){
        $sql = " DELETE FROM $this->table ";
        $tmp = $this->sql_one($sql, $arg);
        $sql = $sql . join(" && ", $arg);
        return $this->pdo->exec($sql);
    }

    function max($col, ...$arg){
        return $this->math('max', $col,...$arg);
    }

    function min($col, ...$arg){
        return $this->math('min', $col,...$arg);
    }
    function sum($col, ...$arg){
        return $this->math('sum', $col,...$arg);
    }

    function paginate($num, $arg=null){
        $total=$this->count($arg);  //取得總筆數
        $pages=ceil($total/$num);   //取得需分為幾頁
        $now=$_GET['p']??1;         //從$_GET取得現在頁數，若無為1
        $start=($now-1)*$num;        //取得本頁要從第幾筆開始撈
        
        $rows= $this->all($arg, " LIMIT $start, $num ");
        // 將以上變數存在陣列中，需要時可使用
        $this->links=[
                "total"=>$total,
                "pages"=>$pages,
                "now"=>$now,
                "start"=>$start,
                "rows"=>$rows,
        ];
    }

    function links($do=null){
        $html="";

        if(is_null($do)){
            $do=$this->table;
        }

        //若為第2頁以上，出現 <
        if( ($this->links['now']-1) >=1){
            $prev= $this->links['now']-1;
            $html.= "<a href='?do=$do&p=$prev'> &lt; </a>";
        }

        // [??SYU??]待註記
        for($i=1; $i<= $this->links['pages']; $i++){
            $html.="<a href='?do=$do&p=$i'> $i </a>";
        }

        //若當前頁碼小於總頁數 ，出現 >
        if(($this->links['now']+1)  <= $this->links['pages']){
            $next= $this->links['now']+1;
            $html.= "<a href='?do=$do&p=$next'> &gt; </a>";
        }

        return $html;
    }



    // STEP2: 建立類別內function，用來組SQL語法用的字串：全、一、數、轉
    protected function sql_all($sql, ...$arg){   
        // $arg第一個參數可能是陣列或字串
        if(isset($arg[0])){
            if(is_array($arg[0])){
                // arg[0]是陣列，將它轉為字串、再整理為SQL語法到$tmp
                $tmp = $this->a2s($arg[0]);
                $sql = $sql . " WHERE " . join(" && ", $tmp);
                //如果$arg第1個參數為字串
            }else{
                $sql = $sql . $arg[0];
            }
        }
        // $arg第二個參數為字串
        if(isset($arg[1])){
            $sql = $sql . $arg[1];
        }
        return $sql;
    }

    //從sql_all內容複製後做修改，會假定$arg參數存在用來做為搜尋資料庫的條件
    protected function sql_one($sql, $arg){
        if(is_array($arg)){
            // arg是陣列(條件>1個)，將它轉為字串、再整理為SQL語法到$tmp
            $tmp = $this->a2s($arg);
            $sql = $sql ." WHERE " .join(" && ", $tmp);
            //如果$arg不是陣列，就假定是用ID來做搜尋條件
        }else{
            $sql = $sql . " WHERE `id`='$arg'";
        }
    
        return $sql;   
    }

    // 與sql_all做法很像  [??SYU??] math()的用途
    protected function math($math, $col, ...$arg){   
        $sql = " SELECT $math($col) from $this->table ";
        $sql = $this->sql_all($sql, ...$arg);
        return $sql;
    }

    protected function a2s($array){   
        // 將輸入的陣列，轉為字串，以供組SQL使用
        foreach($array as $key=>$value){
            if($key!='id'){   //  [??SYU??]為什麼不等於id才執行
                $tmp[]="`$key`='$value'";
            }
        }
        return $tmp;
    }
}
