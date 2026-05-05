using System;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace VPointCare.Web.Migrations
{
    /// <inheritdoc />
    public partial class HangfireJobSettings : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "MPengaturanHangfireJob",
                columns: table => new
                {
                    Id = table.Column<Guid>(type: "uniqueidentifier", nullable: false),
                    KodeJob = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    NamaJob = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    JobIdHangfire = table.Column<string>(type: "varchar(150)", maxLength: 150, nullable: false),
                    CronExpression = table.Column<string>(type: "varchar(100)", maxLength: 100, nullable: false),
                    Aktif = table.Column<bool>(type: "bit", nullable: false),
                    Keterangan = table.Column<string>(type: "varchar(500)", maxLength: 500, nullable: true),
                    NonAktif = table.Column<bool>(type: "bit", nullable: false),
                    TglBuat = table.Column<DateTime>(type: "datetime2", nullable: false),
                    DibuatOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true),
                    TglEdit = table.Column<DateTime>(type: "datetime2", nullable: true),
                    DieditOleh = table.Column<Guid>(type: "uniqueidentifier", nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_MPengaturanHangfireJob", x => x.Id);
                });

            migrationBuilder.CreateIndex(
                name: "IX_MPengaturanHangfireJob_JobIdHangfire",
                table: "MPengaturanHangfireJob",
                column: "JobIdHangfire",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_MPengaturanHangfireJob_KodeJob",
                table: "MPengaturanHangfireJob",
                column: "KodeJob",
                unique: true);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "MPengaturanHangfireJob");
        }
    }
}
