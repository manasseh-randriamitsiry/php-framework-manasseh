<?php
namespace App\Controllers;
use App\Models\AuditModel;

class AuditController{
    protected $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditModel();
    }

    public function audit()
    {
        return view("audit/auditView");
    }

    public function deleteAudit()
    {
        $id = $_POST['id'];
        $this->auditModel->delete($id);
        header("Location: /audit");
    }

}


