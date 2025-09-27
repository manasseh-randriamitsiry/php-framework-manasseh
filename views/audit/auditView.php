<?php
use App\Request;

session_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Audit view</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="../../public/assets/images/favicon.ico">

    <!-- Bootstrap Css -->
    <link href="../../public/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="../../public/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="../../public/assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <style>
        /* Define styles for each operation type */
        .insert-row {
            background-color: #b2ffb2; /* Light green for INSERT */
        }

        .delete-row {
            background-color: #ffb2b2; /* Light red for DELETE */
        }

        .update-row {
            background-color: #b2b2ff; /* Light blue for UPDATE */
        }
    </style>
</head>


<body>
<div id="layout-wrapper">

    <header id="page-topbar" class="isvertical-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- start page title -->
                <div class="page-title-box align-self-center d-none d-md-block">
                    <h4 class="page-title mb-0">Table Audit</h4>
                </div>
                <!-- end page title -->

            </div>

        </div>
    </header>
    <!-- ========== Left Sidebar Start ========== -->
    <div class="vertical-menu">

        <!-- LOGO -->
        <div class="navbar-brand-box">
            <a href="client" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="../../public/assets/images/logo-dark-sm.png" alt="" height="26">
                        </span>
                <span class="logo-lg">
                            <img src="../../public/assets/images/logo-dark.png" alt="" height="28">
                        </span>
            </a>

            <a href="client" class="logo logo-light">
                        <span class="logo-lg">
                            <img src="../../public/assets/images/logo-light.png" alt="" height="30">
                        </span>
                <span class="logo-sm">
                            <img src="../../public/assets/images/logo-light-sm.png" alt="" height="26">
                        </span>
            </a>
        </div>

        <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect vertical-menu-btn">
            <i class="bx bx-menu align-middle"></i>
        </button>

        <div data-simplebar class="sidebar-menu-scroll">

            <!--- Sidemenu -->
            <div id="sidebar-menu">
                <!-- Left Menu Start -->
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li class="menu-title">Actions</li>

                    <li>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="client" >Client</a></li>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="audit" class="font-size-20">Audit</a></li>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="virement" >Virements</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <!-- Sidebar -->
        </div>
    </div>
    <!-- Left Sidebar End -->
    <header class="ishorizontal-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box">
                    <a href="index.html" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="../../public/assets/images/logo-dark-sm.png" alt="" height="26">
                                </span>
                        <span class="logo-lg">
                                    <img src="../../public/assets/images/logo-dark.png" alt="" height="28">
                                </span>
                    </a>

                    <a href="index.html" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="../../public/assets/images/logo-light-sm.png" alt="" height="26">
                                </span>
                        <span class="logo-lg">
                                    <img src="../../public/assets/images/logo-light.png" alt="" height="30">
                                </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 font-size-24 d-lg-none header-item" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                    <i class="bx bx-menu align-middle"></i>
                </button>

                <!-- start page title -->
                <div class="page-title-box align-self-center d-none d-md-block">
                    <h4 class="page-title mb-0">Table Audit</h4>
                </div>
                <!-- end page title -->

            </div>

            <div class="d-flex">
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item noti-icon"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-search icon-sm align-middle"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0">
                        <form class="p-2">
                            <div class="search-box">
                                <div class="position-relative">
                                    <input type="text" class="form-control rounded bg-light border-0" placeholder="Search...">
                                    <i class="bx bx-search search-icon"></i>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item user text-start d-flex align-items-center" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="rounded-circle header-profile-user" src="../../public/assets/images/users/avatar-3.jpg"
                             alt="Header Avatar">
                        <span class="d-none d-xl-inline-block ms-2 fw-medium font-size-15">Martin Gurley</span>
                    </button>
                </div>
            </div>
        </div>


    </header>

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="ccol-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Table Audit</h4>
                                <p class="card-title-desc">Voici les actions faites par l'<code>Utilisateur</code> dans le table
                                    <code>Virement</code>.
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table mb-0" id="operationTable"> <!-- table mb-0-->
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Type action</th>
                                            <th>Date Operation</th>
                                            <th>N°Virement</th>
                                            <th>N°Compte</th>
                                            <th>Nom Client</th>
                                            <th>Date Virement</th>
                                            <th>Montant Ancien</th>
                                            <th>Montant Nouveau</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $query = "SELECT * FROM `audit_virement`";
                                        try {
                                        $statement = connect()->prepare($query);
                                        $statement->execute();
                                        $auditvirements = $statement->fetchAll(\PDO::FETCH_OBJ);
                                        foreach ($auditvirements as $auditvirement):
                                        ?>
                                        <tr>
                                            <td><?php echo $auditvirement->id; ?></td>
                                            <td><?php echo $auditvirement->type_action; ?></td>
                                            <td><?php echo $auditvirement->date_operation; ?></td>
                                            <td><?php echo $auditvirement->n_virement; ?></td>
                                            <td><?php echo $auditvirement->n_compte; ?></td>
                                            <td><?php echo $auditvirement->nom_client; ?></td>
                                            <td><?php echo $auditvirement->date_virement; ?></td>
                                            <td><?php echo $auditvirement->montant_ancien; ?></td>
                                            <td><?php echo $auditvirement->montant_nouv; ?></td>
                                            <td><?php echo $auditvirement->utilisateur; ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modal_delete_<?php echo $auditvirement->id; ?>">delete</a>
                                            </td>
                                        </tr>
                                            <div id="modal_delete_<?php echo $auditvirement->id; ?>" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-scroll="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="post" action="deleteAudit">
                                                            <div class="modal-body">
                                                                <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Supprimer le audit virement <?php echo $auditvirement->id; ?> ?</h5>
                                                            </div>
                                                            <input value="<?php echo $auditvirement->id; ?>" name="id" hidden>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Non</button>
                                                                <button type="submit" class="btn btn-primary ">Oui</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->

                                        <?php
                                        endforeach;
                                        } catch (\SQLiteException $exception) {
                                            die($exception->getMessage());
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <script>document.write(new Date().getFullYear())</script> © administrator.
                    </div>
                    <div class="col-sm-6">
                        <div class="text-sm-end d-none d-sm-block">
                            @manassehrandriamitsiry
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!-- end main content-->

</div>
<!-- END layout-wrapper -->


<!-- JAVASCRIPT -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<!-- JAVASCRIPT -->
<script src="../../public/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    // Get the table
    var table = document.getElementById("operationTable");

    // Loop through each row and apply styles based on the operation type
    for (var i = 1; i < table.rows.length; i++) {
        var operationType = table.rows[i].cells[1].innerText; // Change here

        // Apply styles based on the operation type
        if (operationType === "INSERT") {
            table.rows[i].classList.add("insert-row");
        } else if (operationType === "DELETE") {
            table.rows[i].classList.add("delete-row");
        } else if (operationType === "UPDATE") {
            table.rows[i].classList.add("update-row");
        }
    }
</script>

</body>

</html>
