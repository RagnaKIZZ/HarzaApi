<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    date_default_timezone_set('Asia/Jakarta');
    $container = $app->getContainer();

    // $app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
    //     // Sample log message
    //     $container->get('logger')->info("Slim-Skeleton '/' route");

    //     // Render index view
    //     return $container->get('renderer')->render($response, 'index.phtml', $args);
    // });

    $app->post('/user/register', function ($request, $response) {
        $nama = $request->getParsedBodyParam('nama');
        $email = $request->getParsedBodyParam('email');
        $telepon = $request->getParsedBodyParam('telepon');
        $password = $request->getParsedBodyParam('password');

        $queryTelp = "SELECT * FROM tb_user WHERE telepon = :telepon";

        $queryEmail = "SELECT * FROM tb_user WHERE email = :email";

        $queryInsert = "INSERT INTO tb_user (nama, email, telepon, `password`) VALUES (:nama, :email, :telepon, MD5(:pass))";

        if (empty($telepon) || empty($nama) || empty($email) || empty($password)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi Data"]);
        }


        $stmt = $this->db->prepare($queryTelp);
        if ($stmt->execute([':telepon' => $telepon])) {
            $result = $stmt->fetch();
            $row_telepon = $result['telepon'];
            if ($row_telepon <> null) {
                return $response->withJson(["code" => 201, "msg" => "Email atau nomor telepon telah terdaftar!"]);
            }
        }

        $stmt = $this->db->prepare($queryEmail);
        if ($stmt->execute([':email' => $email])) {
            $result = $stmt->fetch();
            $row_telepon = $result['email'];
            if ($row_telepon <> null) {
                return $response->withJson(["code" => 201, "msg" => "Email atau nomor telepon telah terdaftar!"]);
            }
        }

        $stmt = $this->db->prepare($queryInsert);
        if ($stmt->execute([':nama' => $nama, ':email' => $email, ':telepon' => $telepon, ':pass' => $password])) {
            return $response->withJson(["code" => 200, "msg" => "Berhasil terdaftar!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Gagal terdaftar!"]);
    });


    $app->post('/user/login', function ($request, $response) {
        $email      = $request->getParsedBodyParam('email');
        $password   = $request->getParsedBodyParam('password');
        $token      = hash('sha256', md5(date('Y-m-d H:i:s'), $email));

        if (empty($email) || empty($password)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }

        $query = "SELECT `user_id`,nama, email, telepon, foto, status_login, token_login, token_firebase
                 FROM tb_user WHERE email = :email AND `password` = MD5(:pass)";

        $queryUpdate = "UPDATE tb_user set status_login = '1', token_login = :token WHERE `user_id` = :id ";

        $stmt = $this->db->prepare($query);
        if ($stmt->execute([':email' => $email, ':pass' => $password])) {
            $result = $stmt->fetch();
            $rowIsLogin = $result['status_login'];
            $rowID      = $result['user_id'];
            if ($result) {
                if ($rowIsLogin === "0") {
                    $stmtLogin = $this->db->prepare($queryUpdate);
                    if ($stmtLogin->execute([':id' => $rowID, ':token' => $token])) {
                        $stmt = $this->db->prepare($query);
                        if ($stmt->execute([':email' => $email, ':pass' => $password])) {
                            $result = $stmt->fetch();
                            $rowIsLogin = $result['status_login'];
                            $rowID      = $result['user_id'];
                            if ($result) {
                                return $response->withJson(["code" => 200, "msg" => "Login berhasil!", "data" => $result]);
                            }
                        }
                    } else {
                        return $response->withJson(["code" => 201, "msg" => "Login gagal update status!"]);
                    }
                } else {
                    return $response->withJson(["code" => 201, "msg" => "Anda telah login diperangkat tertentu!"]);
                }
            } else {
                return $response->withJson(["code" => 201, "msg" => "Email atau password salah!"]);
            }
        }
        return $response->withJson(["code" => 201, "msg" => "Email atau password salah!"]);
    });


    $app->post('/user/update_firebase_token', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token_login');
        $token_firebase = $request->getParsedBodyParam('token_firebase');

        if (empty($id) || empty($token_login) || empty($token_firebase)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }

        $query = "UPDATE tb_user set token_firebase = :firebase WHERE `user_id` = :id AND `token_login` = :token_login";

        $stmt = $this->db->prepare($query);
        if ($stmt->execute([':firebase' => $token_firebase, ':id' => $id, ':token_login' => $token_login])) {
            return $response->withJson(["code" => 200, "msg" => "Update token berhasil!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Update token gagal!"]);
    });


    $app->post('/user/update_name', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token_login');
        $nama           = $request->getParsedBodyParam('nama');
        $password       = $request->getParsedBodyParam('password');

        if (empty($nama) || empty($token_login) || empty($id) || empty($password)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }
        $querySelect = "SELECT `user_id`, token_login FROM tb_user WHERE `user_id` = :id AND token_login = :token AND `password` = MD5(:pass)";
        $query = "UPDATE tb_user set nama = :nama WHERE `user_id` = :id AND `token_login` = :token_login AND `password` = MD5(:password)";

        $stmt1 = $this->db->prepare($querySelect);
        if ($stmt1->execute([':id' => $id, ':token' => $token_login, ':pass' => $password])) {
            $result = $stmt1->fetch();
            if ($result) {
                $stmt = $this->db->prepare($query);
                if ($stmt->execute([':nama' => $nama, ':id' => $id, ':token_login' => $token_login, ':password' => $password])) {
                    return $response->withJson(["code" => 200, "msg" => "Update nama berhasil!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Update nama gagal!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Password salah!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Update nama gagal!"]);
    });


    $app->post('/user/update_password', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token_login');
        $password_lama  = $request->getParsedBodyParam('password');
        $password_baru  = $request->getParsedBodyParam('password_baru');

        if (empty($password_baru) || empty($password_lama) || empty($id) || empty($token_login)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }
        $querySelect = "SELECT `user_id`, token_login FROM tb_user WHERE `user_id` = :id AND token_login = :token AND `password` = MD5(:pass)";
        $query = "UPDATE tb_user set `password` = MD5(:password_baru) WHERE `user_id` = :id 
                  AND `token_login` = :token_login AND `password` = MD5(:password_lama)";

        $stmt = $this->db->prepare($querySelect);
        if ($stmt->execute([':id' => $id, ':token' => $token_login, ':pass' => $password_lama])) {
            $result = $stmt->fetch();
            if ($result) {
                $stmt1 = $this->db->prepare($query);
                if ($stmt1->execute([
                    ':id' => $id, ':token_login' => $token_login,
                    ':password_lama' => $password_lama, ':password_baru' => $password_baru
                ])) {
                    return $response->withJson(["code" => 200, "msg" => "Update password berhasil!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Update password gagal!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Password salah!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Update password gagal!"]);
    });


    $app->post('/user/update_email', function ($request, $response) {
        $id          = $request->getParsedBodyParam('id');
        $token_login = $request->getParsedBodyParam('token_login');
        $password    = $request->getParsedBodyParam('password');
        $email       = $request->getParsedBodyParam('email');

        $timeParam   = "";
        $timeUpdate  = date('Y-m-d H:i:s', time());


        if (empty($id) || empty($token_login) || empty($password) || empty($email)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi Data"]);
        }

        $queryEmail = "SELECT * FROM tb_user WHERE email = :email";
        $query = "SELECT `user_id`, token_login, waktu_update 
        FROM tb_user WHERE `user_id` = :id AND token_login = :token AND `password` = MD5(:pass)";

        $queryUpdate = "UPDATE tb_user SET email = :email, waktu_update = :waktu WHERE `user_id` = :id AND `password` = MD5(:pass) ";

        $stmt = $this->db->prepare($queryEmail);
        if ($stmt->execute([':email' => $email])) {
            $result = $stmt->fetch();
            $row_telepon = $result['email'];
            if ($row_telepon <> null) {
                return $response->withJson(["code" => 201, "msg" => "Email telah terdaftar!"]);
            }
        }

        $stmtUpdate = $this->db->prepare($queryUpdate);
        $stmt = $this->db->prepare($query);
        if ($stmt->execute([':id' => $id, ':token' => $token_login, ":pass" => $password])) {
            $result = $stmt->fetch();
            $rowUpdate = $result['waktu_update'];
            $time = strtotime($rowUpdate);
            $time1 = strtotime($timeUpdate);
            $time2 = date('Y-m-d H:i:s', $time + 2 * 24 * 60 * 60);
            $timeP = strtotime($time2);
            $jml = $timeP - $time1;
            $timeParam = floor($jml / (60 * 60 * 24));
            // return $rowUpdate;
            if ($result) {
                if (empty($rowUpdate)) {
                    if ($stmtUpdate->execute([
                        ':id' => $id, ':email' => $email,
                        ':pass' => $password, ':waktu' => $timeUpdate
                    ])) {
                        return $response->withJson(["code" => 200, "msg" => "Update email berhasil!"]);
                    }
                } else {
                    if ($stmtUpdate->execute([
                        ':id' => $id, ':email' => $email,
                        ':pass' => $password, ':waktu' => $timeUpdate
                    ])) {
                        return $response->withJson(["code" => 200, "msg" => "Update email berhasil!"]);
                    }
                }
                return $response->withJson(["code" => 201, "msg" => "Parameter salah!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Password salah!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Parameter salah!"]);
    });


    $app->post('/user/update_telepon', function ($request, $response) {
        $id          = $request->getParsedBodyParam('id');
        $token_login = $request->getParsedBodyParam('token_login');
        $password    = $request->getParsedBodyParam('password');
        $telepon     = $request->getParsedBodyParam('telepon');

        $timeParam   = "";
        $timeUpdate  = date('Y-m-d H:i:s', time());

        // return $timeParam;

        if (empty($id) || empty($token_login) || empty($password) || empty($telepon)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi Data"]);
        }

        $queryTelepon = "SELECT * FROM tb_user WHERE telepon = :telepon";
        $query = "SELECT `user_id`, token_login, waktu_update 
        FROM tb_user WHERE `user_id` = :id AND token_login = :token AND `password` = MD5(:pass)";

        $queryUpdate = "UPDATE tb_user SET telepon = :telepon, waktu_update = :waktu WHERE `user_id` = :id AND `password` = MD5(:pass) ";

        $stmt = $this->db->prepare($queryTelepon);
        if ($stmt->execute([':telepon' => $telepon])) {
            $result = $stmt->fetch();
            $row_telepon = $result['telepon'];
            if ($row_telepon <> null) {
                return $response->withJson(["code" => 201, "msg" => "Nomor telepon telah terdaftar!"]);
            }
        }

        $stmtUpdate = $this->db->prepare($queryUpdate);
        $stmt = $this->db->prepare($query);
        if ($stmt->execute([':id' => $id, ':token' => $token_login, ':pass' => $password])) {
            $result = $stmt->fetch();
            $rowUpdate = $result['waktu_update'];
            $time = strtotime($rowUpdate);
            $time1 = strtotime($timeUpdate);
            $time2 = date('Y-m-d H:i:s', $time + 2 * 24 * 60 * 60);
            $timeP = strtotime($time2);
            $jml = $timeP - $time1;
            $timeParam = floor($jml / (60 * 60 * 24));

            // return $rowUpdate;
            if ($result) {
                if (empty($rowUpdate)) {
                    if ($stmtUpdate->execute([
                        ':id' => $id, ':telepon' => $telepon,
                        ':pass' => $password, ':waktu' => $timeUpdate
                    ])) {
                        return $response->withJson(["code" => 200, "msg" => "Update telepon berhasil!"]);
                    }
                } else {
                    if ($stmtUpdate->execute([
                        ':id' => $id, ':telepon' => $telepon,
                        ':pass' => $password, ':waktu' => $timeUpdate
                    ])) {
                        return $response->withJson(["code" => 200, "msg" => "Update telepon berhasil!"]);
                    }
                }
                return $response->withJson(["code" => 201, "msg" => "Parameter salah!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Password salah!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Parameter salah!"]);
    });


    $app->post('/user/update_foto', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token_login');
        $nama           = "";
        $uploadedFiles  = $request->getUploadedFiles();

        if (empty($id) || empty($token_login)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }

        $queryCheck = "SELECT foto, nama FROM tb_user WHERE `user_id` = :id AND token_login = :token";
        $stmt = $this->db->prepare($queryCheck);
        if ($stmt->execute([':id' => $id, ':token' => $token_login])) {
            $result     = $stmt->fetch();
            $rowFoto    = $result['foto'];
            $nama       = $result['nama'];
            if ($rowFoto <> null) {
                $directory = $this->get('settings')['upload_customer'];
                unlink($directory . '/' . $rowFoto);
            }
        }

        $sql_uuid = "SELECT UUID() as uuid";
        $stmt_uuid = $this->db->prepare($sql_uuid);
        $stmt_uuid->execute();
        $uuid = $stmt_uuid->fetchColumn(0);

        $uploadedFile = $uploadedFiles['foto'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $exetension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $file_name = sprintf('%s.%0.8s', $uuid, $exetension);
            $directory = $this->get('settings')['upload_customer'];
            $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $file_name);

            $sql = "UPDATE tb_user set foto= :foto WHERE `user_id` = :id AND token_login = :token_login";
        }

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([':id' => $id, ':foto' => $file_name, ':token_login' => $token_login])) {
            return $response->withJson(["code" => 200, "msg" => "Foto berhasil di update!", "foto" => $file_name]);
        }
        return $response->withJson(["code" => 201, "msg" => "Foto gagal di update!"]);
    });


    $app->post('/user/hapus_foto', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token_login');

        if (empty($id) || empty($token_login)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }

        $query = "UPDATE tb_user SET foto = '' WHERE `user_id` = :id AND token_login = :token";
        $queryCheck = "SELECT foto FROM tb_user WHERE `user_id` = :id AND token_login = :token";
        $stmt = $this->db->prepare($queryCheck);
        if ($stmt->execute([':id' => $id, ':token' => $token_login])) {
            $result     = $stmt->fetch();
            $rowFoto    = $result['foto'];
            if ($rowFoto <> null) {
                $directory = $this->get('settings')['upload_customer'];
                unlink($directory . '/' . $rowFoto);
                $stmt = $this->db->prepare($query);
                if ($stmt->execute([':id' => $id, ':token' => $token_login])) {
                    return $response->withJson(["code" => 200, "msg" => "Foto berhasil di hapus!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Foto gagal di hapus!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Foto kosong!"]);
        }
    });


    $app->post('/user/logout_user', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token');

        $queryCheck = "SELECT * FROM tb_user WHERE `user_id` = :id AND `token_login` = :token AND status_login = '1'";
        $query = "UPDATE tb_user set status_login = '0', token_firebase = '' WHERE `user_id` = :id AND `token_login` = :token AND status_login = '1'";

        $stmt1 = $this->db->prepare($queryCheck);
        if ($stmt1->execute([':id' => $id, ':token' => $token_login])) {
            $result = $stmt1->fetch();
            if ($result) {
                $stmt = $this->db->prepare($query);
                if ($stmt->execute([':id' => $id, ':token' => $token_login])) {
                    return $response->withJson(["code" => 200, "msg" => "Logout berhasil!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Logout gagal!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Logout gagal1!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Logout gagal!"]);
    });

    $app->post('/user/getListPaket', function ($request, $response) {
        $id             = $request->getParsedBodyParam('id');
        $token_login    = $request->getParsedBodyParam('token');
        $tipe           = $request->getParsedBodyParam('tipe');

        $queryCheck  = "SELECT * FROM tb_user WHERE `user_id` = :id AND `token_login` = :token ";
        $querySelect = "SELECT
                        tb_paket.`nama_paket`,
                        tb_paket.`total_harga`,
                        tb_paket.`status`,
                        tb_paket.`tipe_paket`,
                        tb_paket.`paket_id`,
                        tb_paket.`foto`
                        FROM
                        tb_paket
                        WHERE tb_paket.`tipe_paket` = :tipe ORDER BY tb_paket.`paket_id` ASC";

        $querySelectItem = "SELECT
                            item_paket.nama_item
                            FROM
                            detail_paket
                            INNER JOIN item_paket ON detail_paket.item_id = item_paket.item_id
                            WHERE detail_paket.paket_id = :paket_id";


        $stmt1 = $this->db->prepare($queryCheck);
        $stmt2 = $this->db->prepare($querySelectItem);
        if ($stmt1->execute([':id' => $id, ':token' => $token_login])) {
            $result = $stmt1->fetch();
            if ($result) {
                $stmt = $this->db->prepare($querySelect);
                if ($stmt->execute([':tipe' => $tipe])) {
                    $result = $stmt->fetchAll();
                    if ($result) {
                        for ($i = 0; $i < sizeof($result); $i++) {
                            $paket_id =  $result[$i]['paket_id'];
                            $stmt2->execute([':paket_id' => $paket_id]);
                            $resultItem = $stmt2->fetchAll();
                            $data[$i] = [
                                "paket_id" => $result[$i]['paket_id'],
                                "tipe paket" => $result[$i]['tipe_paket'],
                                "nama_pake" => $result[$i]['nama_paket'],
                                "foto" => $result[$i]['foto'],
                                "harga" => $result[$i]['total_harga'],
                                "status_paket" => $result[$i]['status'],
                                "item_paket" => $resultItem
                            ];
                        }
                        return $response->withJson(["code" => 200, "msg" => "Berhasil mendapatkan data!", "data" => $data]);
                    }
                    return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data1!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data2!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data3!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data!4"]);
    });

    $app->post('/user/make_orderan', function ($request, $response) {
        $id = $request->getParsedBodyParam('id');
        $token = $request->getParsedBodyParam('token');
        $paket_id = $request->getParsedBodyParam('paket_id');
        $s_Date = $request->getParsedBodyParam('start_date');
        $metodePembayaran = $request->getParsedBodyParam('metode');
        $thisdate       = date('Y-m-d H:i:s', time());


        if (empty($id) || empty($token) || empty($paket_id) || empty($s_Date) || empty($metodePembayaran)) {
            return $response->withJson(["code" => 201, "msg" => "Lengkapi data!"]);
        }

        $queryCheck  = "SELECT * FROM tb_user WHERE `user_id` = :id AND `token_login` = :token ";
        $queryCheckOrder  = "SELECT * FROM tb_order WHERE `user_id` = :id AND `status` = '0' ";
        $queryInsert = "INSERT INTO tb_order (`user_id`, `paket_id`, `metode_pembayaran`, `create_date`, `order_date`)
                         VALUES (:u_id, :paket_id, :metode, :c_date, :o_date)";

        $startDate = date('Y-m-d H:i:s', strtotime($s_Date));


        $stmt = $this->db->prepare($queryCheck);
        if ($stmt->execute([':id' => $id, ':token' => $token])) {
            $result = $stmt->fetch();
            if ($result) {
                $stmt = $this->db->prepare($queryCheckOrder);
                if ($stmt->execute([':id' => $id])) {
                    $result = $stmt->fetch();
                    if ($result) {
                        return $response->withJson(["code" => 201, "msg" => "Ada pembayaran yang belum lunas!"]);
                    } else {
                        $stmt = $this->db->prepare($queryInsert);
                        if ($stmt->execute([':u_id' => $id, ':paket_id' => $paket_id, ':metode' => $metodePembayaran, ':c_date' => $thisdate, 'o_date' => $startDate])) {
                            return $response->withJson(["code" => 200, "msg" => "Berhasil dipesan!"]);
                        }
                    }
                }
            }
            return $response->withJson(["code" => 201, "msg" => "Gagal insert order!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Gagal insert order!"]);
    });


    $app->post('/user/cancel_order', function ($request, $response) {
        $id = $request->getParsedBodyParam('id');
        $token = $request->getParsedBodyParam('token');
        $id_order = $request->getParsedBodyParam('order_id');


        $queryCheck = "SELECT * FROM tb_user WHERE `user_id` = :id AND token_login = :token ";

        $queryUpdate = "UPDATE
                        tb_order
                        SET
                        tb_order.`status` = '3'
                        WHERE tb_order.order_id = :order_id ";

        $stmt = $this->db->prepare($queryCheck);
        if ($stmt->execute([':id' => $id, ':token' => $token])) {
            $result = $stmt->fetch();
            if ($result) {
                $stmt = $this->db->prepare($queryUpdate);
                if ($stmt->execute([':order_id' => $id_order])) {
                    return $response->withJson(["code" => 200, "msg" => "Pesanan dibatalkan!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Pesanan gagal dibatalkan1!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Token atau id tidak ditemukan!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Token atau id tidak ditemukan!"]);
    });

    $app->post('/user/getListOrderan', function ($request, $response) {
        $id = $request->getParsedBodyParam('id');
        $token = $request->getParsedBodyParam('token');
        $param = $request->getParsedBodyParam('param');
        $parameter = "";

        if ($param === '1') {
            $parameter = "tb_order.`status` <= 1";
        } else {
            $parameter = "tb_order.`status` >= 2";
        }


        $queryCheck  = "SELECT * FROM tb_user WHERE `user_id` = :id AND `token_login` = :token ";
        $querySelect = "SELECT
                        tb_order.order_id,
                        tb_order.user_id,
                        tb_order.paket_id,
                        tb_order.metode_pembayaran,
                        tb_order.create_date,
                        tb_order.order_date,
                        tb_order.`status`,
                        tb_paket.tipe_paket,
                        tb_paket.nama_paket,
                        tb_paket.foto,
                        tb_paket.total_harga
                        FROM
                        tb_order
                        INNER JOIN tb_paket ON tb_order.paket_id = tb_paket.paket_id
                        WHERE tb_order.`user_id` = :id AND $parameter ORDER BY tb_order.`create_date` DESC";

        $querySelectItem = "SELECT
                            item_paket.nama_item
                            FROM
                            detail_paket
                            INNER JOIN item_paket ON detail_paket.item_id = item_paket.item_id
                            WHERE detail_paket.paket_id = :paket_id";

        $stmt1 = $this->db->prepare($queryCheck);
        $stmt2 = $this->db->prepare($querySelectItem);
        if ($stmt1->execute([':id' => $id, ':token' => $token])) {
            $result = $stmt1->fetch();
            if ($result) {
                $stmt = $this->db->prepare($querySelect);
                if ($stmt->execute([':id' => $id])) {
                    $result = $stmt->fetchAll();
                    if ($result) {
                        for ($i = 0; $i < sizeof($result); $i++) {
                            $paket_id =  $result[$i]['paket_id'];
                            $stmt2->execute([':paket_id' => $paket_id]);
                            $resultItem = $stmt2->fetchAll();
                            $tipe_paket = $result[$i]['tipe_paket'];
                            $tipePaket = "";
                            if ($tipe_paket === '1') {
                                $tipePaket = "Paket Ulang Tahun";
                            } else {
                                $tipePaket = "Paket Pernikahan";
                            }
                            $data[$i] = [
                                "order_id" => $result[$i]['order_id'],
                                "paket_id" => $result[$i]['paket_id'],
                                "tipe paket" => $tipePaket,
                                "nama_pake" => $result[$i]['nama_paket'],
                                "foto" => $result[$i]['foto'],
                                "harga" => $result[$i]['total_harga'],
                                "status_order" => $result[$i]['status'],
                                "waktu_order" => $result[$i]['create_date'],
                                "waktu_acara" => $result[$i]['order_date'],
                                "metode_pembayaran" => $result[$i]['metode_pembayaran'],
                                "item_paket" => $resultItem
                            ];
                        }
                        return $response->withJson(["code" => 200, "msg" => "Berhasil mendapatkan data!", "data" => $data]);
                    }
                    return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data1!"]);
                }
                return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data2!"]);
            }
            return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data3!"]);
        }
        return $response->withJson(["code" => 201, "msg" => "Gagal mendapatkan data!4"]);
    });
};
