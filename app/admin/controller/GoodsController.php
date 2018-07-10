<?php


namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
/**
 * Class GoodsController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '产品管理',
 *     'action' => 'default',
 *     'parent' => '',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   => '',
 *     'remark' => '产品管理'
 * )
 */
class GoodsController extends AdminbaseController {
    
    private $m;
    private $order;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->order='sort asc,time desc';
        $this->m=Db::name('goods');
        $this->assign('flag','产品');
        $units=config('units');
        $packs=config('packs');
        $this->assign('units',$units);
        $this->assign('packs',$packs);
       
    }
    
    /**
     * 产品列表
     * @adminMenu(
     *     'name'   => '产品列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品列表',
     *     'param'  => ''
     * )
     */
    function index(){
        $m=$this->m;
        $where=[];
        $data=$this->request->param();
        if(empty($data['cid'])){
            $data['cid']=0;
        }else{
            $where['cid']=['eq',$data['cid']];
        }
        if(empty($data['name'])){
            $data['name']='';
        }else{
            $where['name']=['like','%'.$data['name'].'%'];
        }
        $list= $m->where($where)->order($this->order)->paginate(10);
        // 获取分页显示
        $page = $list->render();
        
        $cates=Db::name('cate')->order('sort asc')->select();
        $tmp=[];
        foreach($cates as $v){
            $tmp[$v['id']]=$v['name'];
        }
        $this->assign('cates',$tmp);
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->assign('cid',$data['cid']);
        $this->assign('name',$data['name']);
        
        return $this->fetch();
    }
    /**
     * 产品编辑
     * @adminMenu(
     *     'name'   => '产品编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑',
     *     'param'  => ''
     * )
     */
    function edit(){
        $m=$this->m;
        $id=$this->request->param('id');
        $info=$m->where('id',$id)->find();
        $cates=Db::name('cate')->order('sort asc')->select();
        $this->assign('cates',$cates);
        $this->assign('info',$info); 
        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * 产品编辑_执行
     * @adminMenu(
     *     'name'   => '产品编辑_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品编辑_执行',
     *     'param'  => ''
     * )
     */
    function editPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
        if(empty($data['pic'])){
            $this->error('图片上传错误');
        }
        $size=config('goods_pic');
        $data['pic']=zz_set_image($data['pic'], $data['pic'], $size['width'], $size['height'], 6);
       
        $data['time']=time();
        $data['keywords']=empty($data['keywords'])?$data['name']:$data['keywords'];
        $data['content']=empty($_POST['content'])?'':$_POST['content'];
        $row=$m->where('id', $data['id'])->update($data);
        if($row===1){
            $this->success('修改成功',url('index')); 
        }else{
            $this->error('修改失败');
        }
        
    }
    /**
     * 产品删除
     * @adminMenu(
     *     'name'   => '产品删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品删除',
     *     'param'  => ''
     * )
     */
    function delete(){
        $m=$this->m;
        $id=$this->request->param('id'); 
        // 启动事务
        Db::startTrans();
        try{
            $count=Db::name('goods_pros')->where('gid',$id)->count();
            if($count>0){
                throw \Exception();
            }
            Db::name('goods_pros')->where('pid',$id)->delete();
            $row=$m->where('id='.$id)->delete();
            if($row===1){
                Db::commit(); 
            }else{
                throw \Exception(); 
            }
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            if($count>0){
                $this->error('产品在套装内，不能删除');
            }else{
                $this->error('删除失败');
            } 
        }
        $this->success('删除成功');
        
    }
    
    /**
     * 产品添加
     * @adminMenu(
     *     'name'   => '产品添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加',
     *     'param'  => ''
     * )
     */
    public function add(){
       
        $cates=Db::name('cate')->order('sort asc')->select();
       
        $this->assign('cates',$cates);
       
        return $this->fetch();
    }
    
    /**
     * 产品添加1
     * @adminMenu(
     *     'name'   => '产品添加_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品添加_执行',
     *     'param'  => ''
     * )
     */
    public function addPost(){
        
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['pic'])){
            $this->error('图片上传错误');
        }
        $size=config('goods_pic');
        $data['pic']=zz_set_image($data['pic'], $data['pic'], $size['width'], $size['height'], 6);
        
        $data['time']=time();
        $data['insert_time']=time();
        $data['keywords']=empty($data['keywords'])?$data['name']:$data['keywords'];
        $data['content']=empty($_POST['content'])?'':$_POST['content'];
        $row=$m->insertGetId($data);
        if($row>=1){
            $this->success('已成功添加',url('index')); 
        }else{
            $this->error('添加失败');
        }
        exit;
    }
    /**
     * 产品推荐
     * @adminMenu(
     *     'name'   => '产品推荐',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐',
     *     'param'  => ''
     * )
     */
    function top(){
        $m=$this->m;
        $id=$this->request->param('id');
        $info=$m->where('id',$id)->find();
        $workers=Db::name('worker')->order('sort asc')->select();
        $this->assign('workers',$workers);
        $this->assign('info',$info);
        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * 产品推荐_执行
     * @adminMenu(
     *     'name'   => '产品推荐_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐_执行',
     *     'param'  => ''
     * )
     */
    function topPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
        $data= $this->request->param();
        $goods=$m->where('id',$data['id'])->find();
        $worker=DB::name('worker')->where('id',$data['worker'])->find();
        if(empty($goods) || empty($worker)){
            $this->error('产品或大厨不存在了');
        }
        $tmp=[
            'time'=>time(),
            'id1'=>$goods['id'],
            'name1'=>$goods['name'],
            'price'=>$goods['price'],
            'pic1'=>$goods['pic'],
            'dsc1'=>$data['dsc1'],
            'dsc2'=>$data['dsc2'],
            'id2'=>$worker['id'],
            'name2'=>$worker['name'],
            'pic2'=>$worker['pic'],
        ];
        $row=DB::name('goods_top')->insertGetId($tmp);
        if($row>=1){
            $this->success('已成功推荐',url('top0_list'));
        }else{
            $this->error('推荐失败');
        }
       exit;
    }
    /**
     * 产品推荐列表
     * @adminMenu(
     *     'name'   => '产品推荐列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐列表',
     *     'param'  => ''
     * )
     */
    function top0_list(){
        $list= DB::name('goods_top')->order('time desc')->paginate(10);
        // 获取分页显示
        $page = $list->render();
        
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        return $this->fetch();
    }
    /**
     * 产品推荐详情
     * @adminMenu(
     *     'name'   => '产品推荐详情',
     *     'parent' => 'top0_list',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐详情',
     *     'param'  => ''
     * )
     */
    function top0_edit(){
        $m_goods_top=DB::name('goods_top');
        $m=$this->m;
        $id=$this->request->param('id');
        $info=$m_goods_top->where('id',$id)->find();
        if(empty($info)){
            $this->error('该推荐不存在');
        }
        $goods=$m->where('id',$info['id1'])->find();
        $m_work=Db::name('worker');
        $worker=$m_work->where('id',$info['id2'])->find();
        $workers=$m_work->order('sort asc')->select();
        if(empty($goods) || empty($worker)){
            $this->error('产品或推荐人不存在了');
        }
        $this->assign('goods',$goods);
        $this->assign('workers',$workers);
        $this->assign('info',$info);
        //不同类别到不同的页面
        return $this->fetch();
    }
    /**
     * 产品推荐_执行
     * @adminMenu(
     *     'name'   => '产品推荐_执行',
     *     'parent' => 'top0_list',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐_执行',
     *     'param'  => ''
     * )
     */
    function top0Post(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
        $data= $this->request->param();
        $goods=$m->where('id',$data['id'])->find();
        $worker=DB::name('worker')->where('id',$data['worker'])->find();
        if(empty($goods) || empty($worker)){
            $this->error('产品或大厨不存在了');
        }
        $tmp=[
            'time'=>time(),
            'id1'=>$goods['id'],
            'name1'=>$goods['name'],
            'price'=>$goods['price'],
            'pic1'=>$goods['pic'],
            'dsc1'=>$data['dsc1'],
            'dsc2'=>$data['dsc2'],
            'id2'=>$worker['id'],
            'name2'=>$worker['name'],
            'pic2'=>$worker['pic'],
        ];
        $row=DB::name('goods_top')->where('id',$data['id'])->update($tmp);
        if($row===1){
            $this->success('已成功保存',url('top0_list'));
        }else{
            $this->error('保存失败');
        }
        exit;
    }
    /**
     * 产品推荐删除
     * @adminMenu(
     *     'name'   => '产品推荐删除',
     *     'parent' => 'top0_list',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '产品推荐删除',
     *     'param'  => ''
     * )
     */
    function top0_delete(){
        $m=DB::name('goods_top');
        $id=$this->request->param('id');
        $row=$m->where('id='.$id)->delete();
        if($row===1){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
        exit;
    }
    
    
    /**
     * 套装编辑
     * @adminMenu(
     *     'name'   => '套装编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '套装编辑',
     *     'param'  => ''
     * )
     */
    function pros_edit(){
       
        $id=$this->request->param('id');
         
        //已添加的套装产品
        $list=Db::query('select gp.*,g.name,g.unit,g.pack,g.much from cmf_goods_pros as gp left join cmf_goods as g on g.id=gp.gid where gp.pid=?',[$id]);
       //产品分类列表
        $cates=Db::name('cate')->order('sort asc')->select();
       //获取所有产品
        $m=$this->m;
        $info=$m->where('id',$id)->find();
        $goods=$m->field('id,name,cid,unit,pack,much')->where('id','neq',$id)->order('cid asc')->select();
        $this->assign('list',$list);
        $this->assign('cates',$cates);
        $this->assign('goods',$goods);
     
        $this->assign('info',$info);
        
        return $this->fetch();
    }
    /**
     * 套装编辑_执行
     * @adminMenu(
     *     'name'   => '套装编辑_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '套装编辑_执行',
     *     'param'  => ''
     * )
     */
    function pros_editPost(){
        $m=$this->m;
        $data= $this->request->param();
        $info=$m->where('id',$data['id'])->find();
        if(empty($info) || $info['cid']!=config('pros')){
            $this->error('产品不存在或并非套装，请刷新重试');
        }
        if(empty($data['ids'])){
            $this->error('套装内不能没有产品');
        }
         if(empty($data['pic1']) || empty($data['pic2'])){
             $this->error('附属图片必须上传');
         }
         $size=config('pros_pic');
         $data['pic1']=zz_set_image($data['pic1'], $data['pic1'], $size['width'], $size['height'], 6);
         $data['pic2']=zz_set_image($data['pic2'], $data['pic2'], $size['width'], $size['height'], 6);
         
        //先更新主产品
        $time=time();
        $data_goods=[
            'name'=>$data['name'],
            'pic1'=>$data['pic1'],
            'pic2'=>$data['pic2'],
            'time'=>$time,
        ];
        
        
        // 启动事务
        Db::startTrans();
        try{
           $row=$m->where('id',$data['id'])->update($data_goods);
           if($row!==1){
               throw \Exception('goods_pro'); 
           }
           //更新套装内产品
           $data_pros=[];
           foreach($data['ids'] as $v){
               $arr=explode('-', $v);
               $data_pros[]=[
                   'pid'=>$data['id'],
                   'gid'=>$arr[0],
                   'num'=>$arr[1], 
               ];
           }
           //统计条数
           $count=count($data_pros);
           if($count==0){
               throw \Exception('goods_pro'); 
           }
           $m_pros=Db::name('goods_pros');
           $row=$m_pros->where('pid', $data['id'])->delete();
           $row=$m_pros->insertAll($data_pros);
           if($row==$count){
               Db::commit();  
            }else{
                throw \Exception('goods_pro'); 
            }
           
        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('修改失败');
        }
        $this->success('修改成功',url('edit',['id'=>$data['id']]));
         
    }
    
   
}

?>