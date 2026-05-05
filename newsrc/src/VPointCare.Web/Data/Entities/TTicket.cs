using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TTicket")]
public class TTicket
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string NomorTicket { get; set; } = "";

    public Guid? IdChat { get; set; }

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

    public virtual TChat? Chat { get; set; }
    public virtual MCustomer? Customer { get; set; }
    public virtual MInstansi? Instansi { get; set; }
    public virtual MKategoriTicket? KategoriTicket { get; set; }
    public virtual MPrioritasTicket? PrioritasTicket { get; set; }
    public virtual MStatusTicket? StatusTicket { get; set; }
    public virtual TChatD? DibuatDariPesan { get; set; }
    public virtual IEnumerable<TTicketD>? TicketD { get; set; }
    public virtual IEnumerable<TTicketDPenugasan>? TicketDPenugasan { get; set; }
    public virtual IEnumerable<TTicketDLampiran>? TicketDLampiran { get; set; }
    public virtual IEnumerable<TAiPermintaan>? AiPermintaan { get; set; }
}
