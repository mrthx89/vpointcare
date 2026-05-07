using System;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace VPointCare.Web.Migrations
{
    /// <inheritdoc />
    public partial class RefactoringStrukturDB : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "TChatCatatanInternal");

            migrationBuilder.DropTable(
                name: "TChatM");

            migrationBuilder.DropTable(
                name: "TChatPenugasan");

            migrationBuilder.DropTable(
                name: "TTicketLampiran");

            migrationBuilder.DropTable(
                name: "TTicketM");

            migrationBuilder.DropTable(
                name: "TTicketPenugasan");

            migrationBuilder.RenameColumn(
                name: "IdTicketM",
                table: "TTicketD",
                newName: "IdTicket");

            migrationBuilder.RenameColumn(
                name: "IdChatM",
                table: "TChatD",
                newName: "IdChat");

            migrationBuilder.RenameColumn(
                name: "IdTicketM",
                table: "TAiPermintaan",
                newName: "IdTicket");

            migrationBuilder.RenameColumn(
                name: "IdChatM",
                table: "TAiPermintaan",
                newName: "IdChat");

            migrationBuilder.RenameColumn(
                name: "UserId",
                table: "MPengguna",
                newName: "IdUser");

            migrationBuilder.CreateTable(
                name: "TChat",
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
                    table.PrimaryKey("PK_TChat", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TChat_MCustomer_IdCustomer",
                        column: x => x.IdCustomer,
                        principalTable: "MCustomer",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TChat_MGrupWhatsapp_IdGrupWhatsapp",
                        column: x => x.IdGrupWhatsapp,
                        principalTable: "MGrupWhatsapp",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TChat_MInstansi_IdInstansi",
                        column: x => x.IdInstansi,
                        principalTable: "MInstansi",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TChat_MNomorWhatsapp_IdNomorWhatsapp",
                        column: x => x.IdNomorWhatsapp,
                        principalTable: "MNomorWhatsapp",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TChat_MSesiWhatsapp_IdSesiWhatsapp",
                        column: x => x.IdSesiWhatsapp,
                        principalTable: "MSesiWhatsapp",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TChat_MStatusChat_IdStatusChat",
                        column: x => x.IdStatusChat,
                        principalTable: "MStatusChat",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateTable(
                name: "TChatDCatatanInternal",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChat = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IsiCatatan = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatDCatatanInternal", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TChatDCatatanInternal_TChat_IdChat",
                        column: x => x.IdChat,
                        principalTable: "TChat",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateTable(
                name: "TChatDPenugasan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChat = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
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
                    table.PrimaryKey("PK_TChatDPenugasan", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TChatDPenugasan_TChat_IdChat",
                        column: x => x.IdChat,
                        principalTable: "TChat",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateTable(
                name: "TTicket",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NomorTicket = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    IdChat = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
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
                    table.PrimaryKey("PK_TTicket", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TTicket_MCustomer_IdCustomer",
                        column: x => x.IdCustomer,
                        principalTable: "MCustomer",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_MInstansi_IdInstansi",
                        column: x => x.IdInstansi,
                        principalTable: "MInstansi",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_MKategoriTicket_IdKategoriTicket",
                        column: x => x.IdKategoriTicket,
                        principalTable: "MKategoriTicket",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_MPrioritasTicket_IdPrioritasTicket",
                        column: x => x.IdPrioritasTicket,
                        principalTable: "MPrioritasTicket",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_MStatusTicket_IdStatusTicket",
                        column: x => x.IdStatusTicket,
                        principalTable: "MStatusTicket",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_TChatD_DibuatDariPesanId",
                        column: x => x.DibuatDariPesanId,
                        principalTable: "TChatD",
                        principalColumn: "Id");
                    table.ForeignKey(
                        name: "FK_TTicket_TChat_IdChat",
                        column: x => x.IdChat,
                        principalTable: "TChat",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateTable(
                name: "TTicketDLampiran",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
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
                    table.PrimaryKey("PK_TTicketDLampiran", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TTicketDLampiran_TTicket_IdTicket",
                        column: x => x.IdTicket,
                        principalTable: "TTicket",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateTable(
                name: "TTicketDPenugasan",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
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
                    table.PrimaryKey("PK_TTicketDPenugasan", x => x.Id);
                    table.ForeignKey(
                        name: "FK_TTicketDPenugasan_TTicket_IdTicket",
                        column: x => x.IdTicket,
                        principalTable: "TTicket",
                        principalColumn: "Id");
                });

            migrationBuilder.CreateIndex(
                name: "IX_TTicketD_IdTicket",
                table: "TTicketD",
                column: "IdTicket");

            migrationBuilder.CreateIndex(
                name: "IX_TLogWebhookWaha_IdSesiWhatsapp",
                table: "TLogWebhookWaha",
                column: "IdSesiWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_TLogIntegrasi_IdEndpointIntegrasi",
                table: "TLogIntegrasi",
                column: "IdEndpointIntegrasi");

            migrationBuilder.CreateIndex(
                name: "IX_TLogAktivitas_IdPengguna",
                table: "TLogAktivitas",
                column: "IdPengguna");

            migrationBuilder.CreateIndex(
                name: "IX_TChatD_IdAiRespon",
                table: "TChatD",
                column: "IdAiRespon");

            migrationBuilder.CreateIndex(
                name: "IX_TChatD_IdChat",
                table: "TChatD",
                column: "IdChat");

            migrationBuilder.CreateIndex(
                name: "IX_TChatD_IdLogWebhookWaha",
                table: "TChatD",
                column: "IdLogWebhookWaha");

            migrationBuilder.CreateIndex(
                name: "IX_TAiRespon_IdAiPermintaan",
                table: "TAiRespon",
                column: "IdAiPermintaan");

            migrationBuilder.CreateIndex(
                name: "IX_TAiPermintaan_IdAiProvider",
                table: "TAiPermintaan",
                column: "IdAiProvider");

            migrationBuilder.CreateIndex(
                name: "IX_TAiPermintaan_IdChat",
                table: "TAiPermintaan",
                column: "IdChat");

            migrationBuilder.CreateIndex(
                name: "IX_TAiPermintaan_IdTicket",
                table: "TAiPermintaan",
                column: "IdTicket");

            migrationBuilder.CreateIndex(
                name: "IX_MProdukCustomer_IdCustomer",
                table: "MProdukCustomer",
                column: "IdCustomer");

            migrationBuilder.CreateIndex(
                name: "IX_MProdukCustomer_IdInstansi",
                table: "MProdukCustomer",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_MPeranHakAkses_IdHakAkses",
                table: "MPeranHakAkses",
                column: "IdHakAkses");

            migrationBuilder.CreateIndex(
                name: "IX_MPeranHakAkses_IdPeran",
                table: "MPeranHakAkses",
                column: "IdPeran");

            migrationBuilder.CreateIndex(
                name: "IX_MPengguna_IdPeran",
                table: "MPengguna",
                column: "IdPeran");

            migrationBuilder.CreateIndex(
                name: "IX_MPengguna_IdUser",
                table: "MPengguna",
                column: "IdUser");

            migrationBuilder.CreateIndex(
                name: "IX_MNomorWhatsapp_IdCustomer",
                table: "MNomorWhatsapp",
                column: "IdCustomer");

            migrationBuilder.CreateIndex(
                name: "IX_MNomorWhatsapp_IdInstansi",
                table: "MNomorWhatsapp",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_MGrupWhatsapp_IdInstansi",
                table: "MGrupWhatsapp",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_MCustomer_IdInstansi",
                table: "MCustomer",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdCustomer",
                table: "MAnggotaGrupWhatsapp",
                column: "IdCustomer");

            migrationBuilder.CreateIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp",
                table: "MAnggotaGrupWhatsapp",
                column: "IdGrupWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdNomorWhatsapp",
                table: "MAnggotaGrupWhatsapp",
                column: "IdNomorWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdCustomer",
                table: "TChat",
                column: "IdCustomer");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdGrupWhatsapp",
                table: "TChat",
                column: "IdGrupWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdInstansi",
                table: "TChat",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdNomorWhatsapp",
                table: "TChat",
                column: "IdNomorWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdSesiWhatsapp",
                table: "TChat",
                column: "IdSesiWhatsapp");

            migrationBuilder.CreateIndex(
                name: "IX_TChat_IdStatusChat",
                table: "TChat",
                column: "IdStatusChat");

            migrationBuilder.CreateIndex(
                name: "IX_TChatDCatatanInternal_IdChat",
                table: "TChatDCatatanInternal",
                column: "IdChat");

            migrationBuilder.CreateIndex(
                name: "IX_TChatDPenugasan_IdChat",
                table: "TChatDPenugasan",
                column: "IdChat");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_DibuatDariPesanId",
                table: "TTicket",
                column: "DibuatDariPesanId");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdChat",
                table: "TTicket",
                column: "IdChat");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdCustomer",
                table: "TTicket",
                column: "IdCustomer");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdInstansi",
                table: "TTicket",
                column: "IdInstansi");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdKategoriTicket",
                table: "TTicket",
                column: "IdKategoriTicket");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdPrioritasTicket",
                table: "TTicket",
                column: "IdPrioritasTicket");

            migrationBuilder.CreateIndex(
                name: "IX_TTicket_IdStatusTicket",
                table: "TTicket",
                column: "IdStatusTicket");

            migrationBuilder.CreateIndex(
                name: "IX_TTicketDLampiran_IdTicket",
                table: "TTicketDLampiran",
                column: "IdTicket");

            migrationBuilder.CreateIndex(
                name: "IX_TTicketDPenugasan_IdTicket",
                table: "TTicketDPenugasan",
                column: "IdTicket");

            migrationBuilder.AddForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MCustomer_IdCustomer",
                table: "MAnggotaGrupWhatsapp",
                column: "IdCustomer",
                principalTable: "MCustomer",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MGrupWhatsapp_IdGrupWhatsapp",
                table: "MAnggotaGrupWhatsapp",
                column: "IdGrupWhatsapp",
                principalTable: "MGrupWhatsapp",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MNomorWhatsapp_IdNomorWhatsapp",
                table: "MAnggotaGrupWhatsapp",
                column: "IdNomorWhatsapp",
                principalTable: "MNomorWhatsapp",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MCustomer_MInstansi_IdInstansi",
                table: "MCustomer",
                column: "IdInstansi",
                principalTable: "MInstansi",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MGrupWhatsapp_MInstansi_IdInstansi",
                table: "MGrupWhatsapp",
                column: "IdInstansi",
                principalTable: "MInstansi",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MNomorWhatsapp_MCustomer_IdCustomer",
                table: "MNomorWhatsapp",
                column: "IdCustomer",
                principalTable: "MCustomer",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MNomorWhatsapp_MInstansi_IdInstansi",
                table: "MNomorWhatsapp",
                column: "IdInstansi",
                principalTable: "MInstansi",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MPengguna_MPeran_IdPeran",
                table: "MPengguna",
                column: "IdPeran",
                principalTable: "MPeran",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MPengguna_MUser_IdUser",
                table: "MPengguna",
                column: "IdUser",
                principalTable: "MUser",
                principalColumn: "id");

            migrationBuilder.AddForeignKey(
                name: "FK_MPeranHakAkses_MHakAkses_IdHakAkses",
                table: "MPeranHakAkses",
                column: "IdHakAkses",
                principalTable: "MHakAkses",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MPeranHakAkses_MPeran_IdPeran",
                table: "MPeranHakAkses",
                column: "IdPeran",
                principalTable: "MPeran",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MProdukCustomer_MCustomer_IdCustomer",
                table: "MProdukCustomer",
                column: "IdCustomer",
                principalTable: "MCustomer",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_MProdukCustomer_MInstansi_IdInstansi",
                table: "MProdukCustomer",
                column: "IdInstansi",
                principalTable: "MInstansi",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TAiPermintaan_MAiProvider_IdAiProvider",
                table: "TAiPermintaan",
                column: "IdAiProvider",
                principalTable: "MAiProvider",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TAiPermintaan_TChat_IdChat",
                table: "TAiPermintaan",
                column: "IdChat",
                principalTable: "TChat",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TAiPermintaan_TTicket_IdTicket",
                table: "TAiPermintaan",
                column: "IdTicket",
                principalTable: "TTicket",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TAiRespon_TAiPermintaan_IdAiPermintaan",
                table: "TAiRespon",
                column: "IdAiPermintaan",
                principalTable: "TAiPermintaan",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TChatD_TAiRespon_IdAiRespon",
                table: "TChatD",
                column: "IdAiRespon",
                principalTable: "TAiRespon",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TChatD_TChat_IdChat",
                table: "TChatD",
                column: "IdChat",
                principalTable: "TChat",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TChatD_TLogWebhookWaha_IdLogWebhookWaha",
                table: "TChatD",
                column: "IdLogWebhookWaha",
                principalTable: "TLogWebhookWaha",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TLogAktivitas_MPengguna_IdPengguna",
                table: "TLogAktivitas",
                column: "IdPengguna",
                principalTable: "MPengguna",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TLogIntegrasi_MEndpointIntegrasi_IdEndpointIntegrasi",
                table: "TLogIntegrasi",
                column: "IdEndpointIntegrasi",
                principalTable: "MEndpointIntegrasi",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TLogWebhookWaha_MSesiWhatsapp_IdSesiWhatsapp",
                table: "TLogWebhookWaha",
                column: "IdSesiWhatsapp",
                principalTable: "MSesiWhatsapp",
                principalColumn: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_TTicketD_TTicket_IdTicket",
                table: "TTicketD",
                column: "IdTicket",
                principalTable: "TTicket",
                principalColumn: "Id");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MCustomer_IdCustomer",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MGrupWhatsapp_IdGrupWhatsapp",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MAnggotaGrupWhatsapp_MNomorWhatsapp_IdNomorWhatsapp",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MCustomer_MInstansi_IdInstansi",
                table: "MCustomer");

            migrationBuilder.DropForeignKey(
                name: "FK_MGrupWhatsapp_MInstansi_IdInstansi",
                table: "MGrupWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MNomorWhatsapp_MCustomer_IdCustomer",
                table: "MNomorWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MNomorWhatsapp_MInstansi_IdInstansi",
                table: "MNomorWhatsapp");

            migrationBuilder.DropForeignKey(
                name: "FK_MPengguna_MPeran_IdPeran",
                table: "MPengguna");

            migrationBuilder.DropForeignKey(
                name: "FK_MPengguna_MUser_IdUser",
                table: "MPengguna");

            migrationBuilder.DropForeignKey(
                name: "FK_MPeranHakAkses_MHakAkses_IdHakAkses",
                table: "MPeranHakAkses");

            migrationBuilder.DropForeignKey(
                name: "FK_MPeranHakAkses_MPeran_IdPeran",
                table: "MPeranHakAkses");

            migrationBuilder.DropForeignKey(
                name: "FK_MProdukCustomer_MCustomer_IdCustomer",
                table: "MProdukCustomer");

            migrationBuilder.DropForeignKey(
                name: "FK_MProdukCustomer_MInstansi_IdInstansi",
                table: "MProdukCustomer");

            migrationBuilder.DropForeignKey(
                name: "FK_TAiPermintaan_MAiProvider_IdAiProvider",
                table: "TAiPermintaan");

            migrationBuilder.DropForeignKey(
                name: "FK_TAiPermintaan_TChat_IdChat",
                table: "TAiPermintaan");

            migrationBuilder.DropForeignKey(
                name: "FK_TAiPermintaan_TTicket_IdTicket",
                table: "TAiPermintaan");

            migrationBuilder.DropForeignKey(
                name: "FK_TAiRespon_TAiPermintaan_IdAiPermintaan",
                table: "TAiRespon");

            migrationBuilder.DropForeignKey(
                name: "FK_TChatD_TAiRespon_IdAiRespon",
                table: "TChatD");

            migrationBuilder.DropForeignKey(
                name: "FK_TChatD_TChat_IdChat",
                table: "TChatD");

            migrationBuilder.DropForeignKey(
                name: "FK_TChatD_TLogWebhookWaha_IdLogWebhookWaha",
                table: "TChatD");

            migrationBuilder.DropForeignKey(
                name: "FK_TLogAktivitas_MPengguna_IdPengguna",
                table: "TLogAktivitas");

            migrationBuilder.DropForeignKey(
                name: "FK_TLogIntegrasi_MEndpointIntegrasi_IdEndpointIntegrasi",
                table: "TLogIntegrasi");

            migrationBuilder.DropForeignKey(
                name: "FK_TLogWebhookWaha_MSesiWhatsapp_IdSesiWhatsapp",
                table: "TLogWebhookWaha");

            migrationBuilder.DropForeignKey(
                name: "FK_TTicketD_TTicket_IdTicket",
                table: "TTicketD");

            migrationBuilder.DropTable(
                name: "TChatDCatatanInternal");

            migrationBuilder.DropTable(
                name: "TChatDPenugasan");

            migrationBuilder.DropTable(
                name: "TTicketDLampiran");

            migrationBuilder.DropTable(
                name: "TTicketDPenugasan");

            migrationBuilder.DropTable(
                name: "TTicket");

            migrationBuilder.DropTable(
                name: "TChat");

            migrationBuilder.DropIndex(
                name: "IX_TTicketD_IdTicket",
                table: "TTicketD");

            migrationBuilder.DropIndex(
                name: "IX_TLogWebhookWaha_IdSesiWhatsapp",
                table: "TLogWebhookWaha");

            migrationBuilder.DropIndex(
                name: "IX_TLogIntegrasi_IdEndpointIntegrasi",
                table: "TLogIntegrasi");

            migrationBuilder.DropIndex(
                name: "IX_TLogAktivitas_IdPengguna",
                table: "TLogAktivitas");

            migrationBuilder.DropIndex(
                name: "IX_TChatD_IdAiRespon",
                table: "TChatD");

            migrationBuilder.DropIndex(
                name: "IX_TChatD_IdChat",
                table: "TChatD");

            migrationBuilder.DropIndex(
                name: "IX_TChatD_IdLogWebhookWaha",
                table: "TChatD");

            migrationBuilder.DropIndex(
                name: "IX_TAiRespon_IdAiPermintaan",
                table: "TAiRespon");

            migrationBuilder.DropIndex(
                name: "IX_TAiPermintaan_IdAiProvider",
                table: "TAiPermintaan");

            migrationBuilder.DropIndex(
                name: "IX_TAiPermintaan_IdChat",
                table: "TAiPermintaan");

            migrationBuilder.DropIndex(
                name: "IX_TAiPermintaan_IdTicket",
                table: "TAiPermintaan");

            migrationBuilder.DropIndex(
                name: "IX_MProdukCustomer_IdCustomer",
                table: "MProdukCustomer");

            migrationBuilder.DropIndex(
                name: "IX_MProdukCustomer_IdInstansi",
                table: "MProdukCustomer");

            migrationBuilder.DropIndex(
                name: "IX_MPeranHakAkses_IdHakAkses",
                table: "MPeranHakAkses");

            migrationBuilder.DropIndex(
                name: "IX_MPeranHakAkses_IdPeran",
                table: "MPeranHakAkses");

            migrationBuilder.DropIndex(
                name: "IX_MPengguna_IdPeran",
                table: "MPengguna");

            migrationBuilder.DropIndex(
                name: "IX_MPengguna_IdUser",
                table: "MPengguna");

            migrationBuilder.DropIndex(
                name: "IX_MNomorWhatsapp_IdCustomer",
                table: "MNomorWhatsapp");

            migrationBuilder.DropIndex(
                name: "IX_MNomorWhatsapp_IdInstansi",
                table: "MNomorWhatsapp");

            migrationBuilder.DropIndex(
                name: "IX_MGrupWhatsapp_IdInstansi",
                table: "MGrupWhatsapp");

            migrationBuilder.DropIndex(
                name: "IX_MCustomer_IdInstansi",
                table: "MCustomer");

            migrationBuilder.DropIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdCustomer",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdGrupWhatsapp",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.DropIndex(
                name: "IX_MAnggotaGrupWhatsapp_IdNomorWhatsapp",
                table: "MAnggotaGrupWhatsapp");

            migrationBuilder.RenameColumn(
                name: "IdTicket",
                table: "TTicketD",
                newName: "IdTicketM");

            migrationBuilder.RenameColumn(
                name: "IdChat",
                table: "TChatD",
                newName: "IdChatM");

            migrationBuilder.RenameColumn(
                name: "IdTicket",
                table: "TAiPermintaan",
                newName: "IdTicketM");

            migrationBuilder.RenameColumn(
                name: "IdChat",
                table: "TAiPermintaan",
                newName: "IdChatM");

            migrationBuilder.RenameColumn(
                name: "IdUser",
                table: "MPengguna",
                newName: "UserId");

            migrationBuilder.CreateTable(
                name: "TChatCatatanInternal",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IsiCatatan = table.Column<string>(type: "nvarchar(max)", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatCatatanInternal", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TChatM",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    AiSudahMenyapa = table.Column<bool>(type: "bit", nullable: false),
                    AutoReplyAiAktif = table.Column<bool>(type: "bit", nullable: false),
                    DiambilOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitutupOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdGrupWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdNomorWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdSesiWhatsapp = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdStatusChat = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdWahaTerdeteksi = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    JenisChat = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: false),
                    JumlahNotifikasiBelumTerbalas = table.Column<int>(type: "int", nullable: false),
                    JumlahPesanBelumDibaca = table.Column<int>(type: "int", nullable: false),
                    ModeAutoReplyAi = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    NamaGrupWhatsapp = table.Column<string>(type: "varchar(200)", maxLength: 200, nullable: true),
                    NamaKontak = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: true),
                    NomorWhatsapp = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: false),
                    NomorWhatsappTerdeteksi = table.Column<string>(type: "varchar(30)", maxLength: 30, nullable: true),
                    Prioritas = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    RingkasanAi = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglAutoReplyAiTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglChatTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDiambil = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDibalasTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDitutup = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglFotoProfilDiambil = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglNotifikasiBelumTerbalasTerakhir = table.Column<DateTime>(type: "datetime2", nullable: true),
                    UrlFotoProfil = table.Column<string>(type: "nvarchar(1000)", maxLength: 1000, nullable: true)
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
                    AlasanPenugasan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanDari = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglPenugasan = table.Column<DateTime>(type: "datetime2", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TChatPenugasan", x => x.Id);
                });

            migrationBuilder.CreateTable(
                name: "TTicketLampiran",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    NamaFile = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    PathFile = table.Column<string>(type: "varchar(1000)", maxLength: 1000, nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TipeFile = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: true),
                    UkuranFile = table.Column<long>(type: "bigint", nullable: true)
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
                    DeskripsiMasalah = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    DibuatDariPesanId = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitutupOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdChatM = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdCustomer = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdInstansi = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdKategoriTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdPrioritasTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    IdStatusTicket = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    JudulTicket = table.Column<string>(type: "varchar(255)", maxLength: 255, nullable: false),
                    NomorTicket = table.Column<string>(type: "varchar(50)", maxLength: 50, nullable: false),
                    RingkasanAi = table.Column<string>(type: "nvarchar(max)", nullable: true),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglDitugaskan = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglDitutup = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglSelesai = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglTargetSelesai = table.Column<DateTime>(type: "datetime2", nullable: true)
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
                    AlasanPenugasan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanDari = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    DitugaskanKepada = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    IdTicketM = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    TglPenugasan = table.Column<DateTime>(type: "datetime2", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_TTicketPenugasan", x => x.Id);
                });
        }
    }
}
