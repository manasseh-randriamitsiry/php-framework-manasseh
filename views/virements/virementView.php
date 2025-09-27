<?php
use App\Request;
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
echo ("alefa");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Virement view</title>
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

</head>


<body>
<div id="layout-wrapper">

    <header id="page-topbar" class="isvertical-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- title -->
                <div class="page-title-box align-self-center d-none d-md-block">
                    <h4 class="page-title mb-0">Table virement</h4>
                </div>
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
                    <li class="menu-title" data-key="t-menu">Actions</li>

                    <li>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="client">Client</a></li>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="audit" >Audit</a></li>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <li><a href="virement" class="font-size-20" >Virement</a></li>
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
                <button type="button" class="btn btn-sm px-3 font-size-24 d-lg-none header-item" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                    <i class="bx bx-menu align-middle"></i>
                </button>

                <!-- start page title -->
                <div class="page-title-box align-self-center d-none d-md-block">
                    <h4 class="page-title mb-0">Table virement</h4>
                </div>
                <!-- end page title -->

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
                    <div class="col-xl-12">
                        <div class="card">
                            <?php

                            // Check if a message is set in the session
                            if (isset($_SESSION['message'])) {
                                echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
                               echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img" aria-label="Warning:">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                </svg>';
                                echo $_SESSION['message'];
                                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                                echo '</div>';

                                // Clear the message from the session to ensure it's not displayed again
                                unset($_SESSION['message']);
                            }
                            ?>
                            <div class="card-header">
                                <h4 class="card-title">
                                    Table virement
                                    <button class="btn btn-outline-success mx-5" data-bs-toggle="modal" data-bs-target="#modal_ajout">+ Ajouter</button>
                                </h4>
                                <p class="card-title-desc">On fait des virements ici sans se soucier et le verifie dans le table  <code>Audit Virement </code>
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table mb-0"> <!-- table mb-0-->
                                        <thead>
                                        <tr>
                                            <th>N° Virement</th>
                                            <th>N° Compte</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                            <th>Operations</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $query = "SELECT * from virement";
                                        try {
                                        $statement = connect()->prepare($query);
                                        $statement->execute();
                                        $virements = $statement->fetchAll(\PDO::FETCH_OBJ);
                                        foreach ($virements as $virement):
                                        ?>
                                        <tr>
                                            <td><?php echo $virement->n_virement; ?></td>
                                            <td><?php echo $virement->n_compte; ?></td>
                                            <td><?php echo $virement->montant; ?></td>
                                            <td><?php echo $virement->date; ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal_edit_<?php echo $virement->n_virement; ?>">edit</a>
                                                <a class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modal_delete_<?php echo $virement->n_virement; ?>">delete</a>
                                            </td>
                                        </tr>

                                        <div id="modal_edit_<?php echo $virement->n_virement; ?>" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-scroll="true">
                                            <div class="modal-dialog">
                                                <form method="post" action="editVirement">
                                                    <div class="modal-content">
                                                        <div class="modal-body">
                                                            <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Formulaire edit virement</h5>
                                                            <div class="mb-3">
                                                                <label class="form-label" for="n_virement">N°Virement</label>
                                                                <input type="number" class="form-control" placeholder="Entrer le numero de virement" id="n_virement" name="n_virement" value="<?php echo $virement->n_virement; ?>" readonly>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label" for="n_compte">N° Compte</label>
                                                                        <input type="number" class="form-control" placeholder="Entrer le numero de compte" id="n_compte" name="n_compte" value="<?php echo $virement->n_compte; ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <label class="form-label" for="montant">Montant</label>
                                                                        <input type="number" class="form-control" placeholder="Entrer le montant" id="montant" name="montant" value="<?php echo $virement->montant; ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label" for="date_virement">Date</label>
                                                                <input type="date" class="form-control" placeholder="Date de viremennt" id="date_virement" name="date_virement" value="<?php echo $virement->date; ?>">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">fermer</button>
                                                            <button type="submit" class="btn btn-primary waves-effect waves-light">Sauvegarder</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div><!-- /.modal-content -->
                                        </div>
                                        <div id="modal_delete_<?php echo $virement->n_virement; ?>" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-scroll="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="deleteVirement">
                                                        <div class="modal-body">
                                                            <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Supprimer le client <?php echo $virement->n_virement; ?> ?</h5>
                                                        </div>
                                                        <input value="<?php echo $virement->n_virement; ?>" name="n_virement" hidden>
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
        <div id="modal_ajout" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true" data-bs-scroll="true">
            <div class="modal-dialog">
                <form method="post" action="addVirement">
                <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="font-size-14 mb-4"><i class="mdi mdi-arrow-right text-primary me-1"></i> Formulaire ajout virement</h5>
                            <div class="mb-3">
                                <label class="form-label" for="n_virement">N°Virement</label>
                                <input type="number" class="form-control" placeholder="Entrer le numero de virement" id="n_virement" name="n_virement">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="n_compte">N° Compte</label>
                                        <input type="number" class="form-control" placeholder="Entrer le numero de compte" id="n_compte" name="n_compte">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="montant">Montant</label>
                                        <input type="number" class="form-control" placeholder="Entrer le montant" id="montant" name="montant">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="date_virement">Date</label>
                                <input type="date" class="form-control" placeholder="Date de viremennt" id="date_virement" name="date_virement">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">fermer</button>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">Sauvegarder</button>
                        </div>
                </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->

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


<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

<!-- JAVASCRIPT -->
<script src="../../public/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>


</body>

</html>