using System;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace VPointCare.Web.Migrations
{
    /// <inheritdoc />
    public partial class InitialCreate : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "MAiProvider",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeProvider = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaProvider = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    BaseUrl = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    ApiKeyTerenkripsi = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: true),
                    ModelDefault = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MAiProvider", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MAnggotaGrupWhatsapp",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdGrupWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdNomorWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    PeranAnggota = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MAnggotaGrupWhatsapp", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MCustomer",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    KodeCustomer = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaCustomer = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    Email = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    Telepon = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    Jabatan = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    Catatan = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: true),
                    SumberData = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    IdExternal = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    TglSinkronTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MCustomer", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MEndpointIntegrasi",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeEndpoint = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    NamaEndpoint = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    UrlEndpoint = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: false),
                    MetodeHttp = table.Column<string>(type: "varchar(10)", maxLength: 10, nullable: false),
                    HeaderJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MEndpointIntegrasi", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MGrupWhatsapp",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeGrup = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaGrup = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    IdGrupWaha = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    NomorGrupWhatsapp = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    Deskripsi = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    SumberData = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    IdExternal = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MGrupWhatsapp", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MHakAkses",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeHakAkses = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    NamaHakAkses = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    Modul = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MHakAkses", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MHariLibur",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    TanggalLibur = table.Column<DateTime>(type: "datetime2", nullable: false),
                    NamaHariLibur = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: true),
                    BerlakuTahunan = table.Column<bool>(type: "bit", nullable: false),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MHariLibur", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MInstansi",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeInstansi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaInstansi = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    Alamat = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    Kota = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    Provinsi = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    Negara = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    KodePos = table.Column<string>(type: "varchar(20)", maxLength: 20, nullable: true),
                    Telepon = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    Email = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    Website = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    SumberData = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    IdExternal = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    TglSinkronTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MInstansi", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MKategoriTicket",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeKategori = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaKategori = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MKategoriTicket", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MNomorWhatsapp",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    NomorWhatsapp = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: false),
                    NamaKontak = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    JabatanKontak = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    NomorUtama = table.Column<bool>(type: "bit", nullable: false),
                    Terverifikasi = table.Column<bool>(type: "bit", nullable: false),
                    SumberData = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    IdExternal = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MNomorWhatsapp", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPengaturanAi",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodePengaturan = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaPengaturan = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    AutoReplyAktif = table.Column<bool>(type: "bit", nullable: false),
                    AutoReplyDiluarJamKerja = table.Column<bool>(type: "bit", nullable: false),
                    AutoReplyHariLibur = table.Column<bool>(type: "bit", nullable: false),
                    AutoReplyJamKerjaSapaan = table.Column<bool>(type: "bit", nullable: false),
                    AutoReplyJamKerjaBerlanjut = table.Column<bool>(type: "bit", nullable: false),
                    JamKerjaMulai = table.Column<TimeSpan>(type: "time(0)", nullable: false),
                    JamKerjaSelesai = table.Column<TimeSpan>(type: "time(0)", nullable: false),
                    HariKerja = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    ZonaWaktu = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    ProviderAi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    ModelAi = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    BaseUrl = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    ApiKeyTerenkripsi = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    PromptSistem = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TemplateDiluarJamKerja = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TemplateHariLibur = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TemplateJamKerjaSapaan = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TemplateFallback = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    NotifikasiChatBelumTerbalasAktif = table.Column<bool>(type: "bit", nullable: false),
                    MenitTungguNotifikasi = table.Column<int>(type: "int", nullable: false),
                    JedaNotifikasiMenit = table.Column<int>(type: "int", nullable: false),
                    KodePeranPenerimaNotifikasi = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    TemplateNotifikasiChatBelumTerbalas = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    BatasRiwayatPesan = table.Column<int>(type: "int", nullable: false),
                    KirimKeWaha = table.Column<bool>(type: "bit", nullable: false),
                    ModeKirim = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPengaturanAi", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPengetahuan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodePengetahuan = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    JudulPengetahuan = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: false),
                    IsiPengetahuan = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    Tag = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPengetahuan", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPengguna",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    UserId = table.Column<long>(type: "bigint", nullable: true),
                    IdPeran = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NamaPengguna = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    Email = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    Password = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    NomorWhatsappInternal = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    FotoProfilPath = table.Column<string>(type: "nvarchar(500)", maxLength: 500, nullable: true),
                    Jabatan = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    RememberToken = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    EmailTerverifikasiPada = table.Column<DateTime>(type: "datetime2", nullable: true),
                    LoginTerakhirPada = table.Column<DateTime>(type: "datetime2", nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPengguna", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPeran",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodePeran = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaPeran = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPeran", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPeranHakAkses",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdPeran = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdHakAkses = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPeranHakAkses", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MPrioritasTicket",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodePrioritas = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaPrioritas = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Urutan = table.Column<int>(type: "int", nullable: false),
                    BatasSlaMenit = table.Column<int>(type: "int", nullable: true),
                    Warna = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPrioritasTicket", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MProdukCustomer",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    KodeProduk = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaProduk = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    TglMulai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglBerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MProdukCustomer", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MSesiWhatsapp",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeSesi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaSesi = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    BaseUrlWaha = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    ApiKey = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    NomorTerhubung = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    StatusSesi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    WebhookToken = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MSesiWhatsapp", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MStatusChat",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeStatusChat = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaStatusChat = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Urutan = table.Column<int>(type: "int", nullable: false),
                    Warna = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MStatusChat", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MStatusTicket",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeStatusTicket = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaStatusTicket = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Urutan = table.Column<int>(type: "int", nullable: false),
                    StatusFinal = table.Column<bool>(type: "bit", nullable: false),
                    Warna = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MStatusTicket", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "MUser",
                columns: table => new
                {
                    id = table.Column<long>(type: "bigint", nullable: false)
                        .Annotation("SqlServer:Identity", "1, 1"),
                    name = table.Column<string>(type: "nvarchar(255)", maxLength: 255, nullable: false),
                    email = table.Column<string>(type: "nvarchar(255)", maxLength: 255, nullable: false),
                    email_verified_at = table.Column<DateTime>(type: "datetime2", nullable: true),
                    password = table.Column<string>(type: "nvarchar(255)", maxLength: 255, nullable: false),
                    remember_token = table.Column<string>(type: "nvarchar(100)", maxLength: 100, nullable: true),
                    status = table.Column<string>(type: "nvarchar(20)", maxLength: 20, nullable: false),
                    approved_at = table.Column<DateTime>(type: "datetime2", nullable: true),
                    blocked_at = table.Column<DateTime>(type: "datetime2", nullable: true),
                    created_at = table.Column<DateTime>(type: "datetime2", nullable: true),
                    updated_at = table.Column<DateTime>(type: "datetime2", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MUser", x => x.id);
                });

            migrationBuilder.CreateTable(
                name: "TAiPermintaan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdAiProvider = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    JenisPermintaan = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    ProviderAi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    ModelAi = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    PromptRingkas = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    PromptJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    StatusPermintaan = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    TglMulai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglSelesai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    PesanError = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TAiPermintaan", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TAiRespon",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdAiPermintaan = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    JenisRespon = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    ResponRingkas = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    ResponJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TokenInput = table.Column<int>(type: "int", nullable: true),
                    TokenOutput = table.Column<int>(type: "int", nullable: true),
                    BiayaEstimasi = table.Column<decimal>(type: "decimal(18,2)", nullable: true),
                    DisetujuiOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglDisetujui = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TAiRespon", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TChatCatatanInternal",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IsiCatatan = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatCatatanInternal", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TChatD",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdLogWebhookWaha = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdPesanWaha = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    ArahPesan = table.Column<string>(type: "varchar(20)", maxLength: 20, nullable: false),
                    JenisPesan = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    IsiPesan = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    UrlMedia = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: true),
                    NamaFileMedia = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: true),
                    TipeMime = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    PayloadJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    PengirimNomorWhatsapp = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    PengirimNamaKontak = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    DikirimOlehCustomer = table.Column<bool>(type: "bit", nullable: false),
                    DihasilkanOlehAi = table.Column<bool>(type: "bit", nullable: false),
                    IdAiRespon = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DibalasOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglPesan = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglDikirim = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDibaca = table.Column<DateTime>(type: "datetime2", nullable: true),
                    StatusKirim = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    PesanError = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatD", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TChatM",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdSesiWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdStatusChat = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdNomorWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdGrupWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    JenisChat = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: false),
                    NomorWhatsapp = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: false),
                    NamaKontak = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    NamaGrupWhatsapp = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    IdWahaTerdeteksi = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    NomorWhatsappTerdeteksi = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    UrlFotoProfil = table.Column<string>(type: "nvarchar(1000)", maxLength: 1000, nullable: true),
                    TglFotoProfilDiambil = table.Column<DateTime>(type: "datetime2", nullable: true),
                    Prioritas = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DiambilOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglDiambil = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglChatTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDibalasTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    JumlahPesanBelumDibaca = table.Column<int>(type: "int", nullable: false),
                    DitutupOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglDitutup = table.Column<DateTime>(type: "datetime2", nullable: true),
                    RingkasanAi = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    AutoReplyAiAktif = table.Column<bool>(type: "bit", nullable: false),
                    AiSudahMenyapa = table.Column<bool>(type: "bit", nullable: false),
                    ModeAutoReplyAi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    TglAutoReplyAiTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglNotifikasiBelumTerbalasTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    JumlahNotifikasiBelumTerbalas = table.Column<int>(type: "int", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatM", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TChatPenugasan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    DitugaskanDari = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    AlasanPenugasan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    TglPenugasan = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatPenugasan", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TLogAktivitas",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdPengguna = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    Modul = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Aksi = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: true),
                    IpAddress = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: true),
                    UserAgent = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    DataSebelumJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    DataSesudahJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglAktivitas = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TLogAktivitas", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TLogError",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    LevelError = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    PesanError = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    FileError = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    BarisError = table.Column<int>(type: "int", nullable: true),
                    StackTrace = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    ContextJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglError = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TLogError", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TLogIntegrasi",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdEndpointIntegrasi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    KodeIntegrasi = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    UrlEndpoint = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: false),
                    MetodeHttp = table.Column<string>(type: "varchar(10)", maxLength: 10, nullable: false),
                    RequestJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    ResponseJson = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    StatusHttp = table.Column<int>(type: "int", nullable: true),
                    Berhasil = table.Column<bool>(type: "bit", nullable: false),
                    PesanError = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglRequest = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglResponse = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TLogIntegrasi", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TLogWebhookWaha",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdSesiWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    JenisEvent = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    PayloadJson = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    TglDiterima = table.Column<DateTime>(type: "datetime2", nullable: false),
                    SudahDiproses = table.Column<bool>(type: "bit", nullable: false),
                    TglDiproses = table.Column<DateTime>(type: "datetime2", nullable: true),
                    PesanError = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TLogWebhookWaha", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TTicketD",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    JenisAktivitas = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    IsiAktivitas = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    StatusSebelum = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    StatusSesudah = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    DitujukanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglAktivitas = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TTicketD", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TTicketLampiran",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NamaFile = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    PathFile = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: false),
                    TipeFile = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    UkuranFile = table.Column<long>(type: "bigint", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TTicketLampiran", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TTicketM",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NomorTicket = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdKategoriTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdPrioritasTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdStatusTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    JudulTicket = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    DeskripsiMasalah = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    DibuatDariPesanId = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglDitugaskan = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglTargetSelesai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglSelesai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDitutup = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DitutupOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    RingkasanAi = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TTicketM", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TTicketPenugasan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    DitugaskanDari = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    AlasanPenugasan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    TglPenugasan = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TTicketPenugasan", x => x.Id);
                });

            migrationBuilder.CreateIndex(
                name: "IX_MPengguna_Email",
                table: "MPengguna",
                column: "Email",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_MSesiWhatsapp_KodeSesi",
                table: "MSesiWhatsapp",
                column: "KodeSesi",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_MUser_email",
                table: "MUser",
                column: "email",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_TChatD_IdPesanWaha",
                table: "TChatD",
                column: "IdPesanWaha");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "MAiProvider");

            migrationBuilder.DropTable(
                name: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropTable(
                name: "MCustomer");

            migrationBuilder.DropTable(
                name: "MEndpointIntegrasi");

            migrationBuilder.DropTable(
                name: "MGrupWhatsapp");

            migrationBuilder.DropTable(
                name: "MHakAkses");

            migrationBuilder.DropTable(
                name: "MHariLibur");

            migrationBuilder.DropTable(
                name: "MInstansi");

            migrationBuilder.DropTable(
                name: "MKategoriTicket");

            migrationBuilder.DropTable(
                name: "MNomorWhatsapp");

            migrationBuilder.DropTable(
                name: "MPengaturanAi");

            migrationBuilder.DropTable(
                name: "MPengetahuan");

            migrationBuilder.DropTable(
                name: "MPengguna");

            migrationBuilder.DropTable(
                name: "MPeran");

            migrationBuilder.DropTable(
                name: "MPeranHakAkses");

            migrationBuilder.DropTable(
                name: "MPrioritasTicket");

            migrationBuilder.DropTable(
                name: "MProdukCustomer");

            migrationBuilder.DropTable(
                name: "MSesiWhatsapp");

            migrationBuilder.DropTable(
                name: "MStatusChat");

            migrationBuilder.DropTable(
                name: "MStatusTicket");

            migrationBuilder.DropTable(
                name: "MUser");

            migrationBuilder.DropTable(
                name: "TAiPermintaan");

            migrationBuilder.DropTable(
                name: "TAiRespon");

            migrationBuilder.DropTable(
                name: "TChatCatatanInternal");

            migrationBuilder.DropTable(
                name: "TChatD");

            migrationBuilder.DropTable(
                name: "TChatM");

            migrationBuilder.DropTable(
                name: "TChatPenugasan");

            migrationBuilder.DropTable(
                name: "TLogAktivitas");

            migrationBuilder.DropTable(
                name: "TLogError");

            migrationBuilder.DropTable(
                name: "TLogIntegrasi");

            migrationBuilder.DropTable(
                name: "TLogWebhookWaha");

            migrationBuilder.DropTable(
                name: "TTicketD");

            migrationBuilder.DropTable(
                name: "TTicketLampiran");

            migrationBuilder.DropTable(
                name: "TTicketM");

            migrationBuilder.DropTable(
                name: "TTicketPenugasan");
        }
    }
}
