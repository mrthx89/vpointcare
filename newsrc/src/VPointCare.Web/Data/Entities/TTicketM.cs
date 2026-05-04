using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TTicketM")]
public class TTicketM
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string NomorTicket { get; set; } = "";

    public Guid? IdChatM { get; set; }

    public Guid? IdCustomer { get; set; }

    public Guid? IdInstansi { get; set; }

    public Guid? IdKategoriTicket { get; set; }

    public Guid? IdPrioritasTicket { get; set; }

    public Guid? IdStatusTicket { get; set; }

    [StringLength(255)]
    public string JudulTicket { get; set; } = "";

    public string? DeskripsiMasalah { get; set; }

    public Guid? DibuatDariPesanId { get; set; }

    public Guid? DitugaskanKepada { get; set; }

    public DateTime? TglDitugaskan { get; set; }

    public DateTime? TglTargetSelesai { get; set; }

    public DateTime? TglSelesai { get; set; }

    public DateTime? TglDitutup { get; set; }

    public Guid? DitutupOleh { get; set; }

    public string? RingkasanAi { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
