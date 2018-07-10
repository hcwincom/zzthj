<?php


namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
 

 
class CompanyController extends AdminbaseController {
    
    private $m;
    private $order;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->order='type asc,name asc';
        $this->m=Db::name('Company');
        $this->assign('flag','网站信息配置');
    }
    
    /**
     * 网站信息列表
     * @adminMenu(
     *     'name'   => '网站信息管理',
     *     'parent' => '',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息管理',
     *     'param'  => ''
     * )
     */
    function index(){
        $m=$this->m;
        
        $list= $m->order($this->order)->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('page',$page);
        $this->assign('list',$list);
        
        return $this->fetch();
    }
    /**
     * 网站信息编辑
     * @adminMenu(
     *     'name'   => '网站信息编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息编辑',
     *     'param'  => ''
     * )
     */
    function edit(){
        $m=$this->m;
        $id=$this->request->param('id');
        $info=$m->where('id',$id)->find();
        $this->assign('info',$info); 
        //不同类别到不同的页面
        return $this->fetch('edit'.$info['type']);
    }
    /**
     * 网站信息编辑1
     * @adminMenu(
     *     'name'   => '网站信息编辑_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息编辑_执行',
     *     'param'  => ''
     * )
     */
    function editPost(){
        $m=$this->m;
        $data= $this->request->param();
        if(empty($data['id'])){
            $this->error('数据错误');
        }
         
        $data= $this->request->param();
        $data['time']=time();
        $row=$m->where('id', $data['id'])->update($data);
        if($row===1){
            $this->success('修改成功',url('index')); 
        }else{
            $this->error('修改失败');
        }
        
    }
    /**
     * 网站信息删除
     * @adminMenu(
     *     'name'   => '网站信息删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息删除',
     *     'param'  => ''
     * )
     */
    function delete(){
        $m=$this->m;
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
     * 网站信息添加
     * @adminMenu(
     *     'name'   => '网站信息添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息添加',
     *     'param'  => ''
     * )
     */
    public function add(){
        
        return $this->fetch();
    }
    
    /**
     * 网站信息添加1
     * @adminMenu(
     *     'name'   => '网站信息添加_执行',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10,
     *     'icon'   => '',
     *     'remark' => '网站信息添加_执行',
     *     'param'  => ''
     * )
     */
    public function addPost(){
        
        $m=$this->m;
        $data= $this->request->param();
        $data['time']=time();
        $row=$m->insertGetId($data);
        if($row>=1){
            
            $this->success('已成功添加',url('index')); 
        }else{
            $this->error('添加失败');
        }
        exit;
    }
     
    
    
}

?>