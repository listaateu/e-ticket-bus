<?php

class Database
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "e-ticket_bus";

    public $koneksi;

    public function __construct()
    {
        $this->koneksi = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->koneksi->connect_error) {
            die("Koneksi gagal: " . $this->koneksi->connect_error);
        }

        $this->koneksi->set_charset("utf8mb4");
    }
}

// ----- bagian tambahan -----
$db = new Database();     // "mencetak" class jadi objek, saat ini koneksi dibuka
$koneksi = $db->koneksi;     // ambil koneksinya, namanya disamakan dengan yang dipakai di data.php